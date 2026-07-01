const express = require('express');
const cors = require('cors');
const crypto = require('crypto');
const mysql = require('mysql2/promise');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

// MySQL connection pool configuration
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'php_node_ecommerce',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

const pool = mysql.createPool(dbConfig);

// Helper function to check database connection
async function checkConnection() {
  try {
    const connection = await pool.getConnection();
    console.log('Database connected successfully!');
    connection.release();
  } catch (err) {
    console.error('Error connecting to MySQL database:', err.message);
  }
}
checkConnection();

// Password Hashing Helper
function hashPassword(password) {
  return crypto.createHash('sha256').update(password).digest('hex');
}

// GET /api/products - Get all products with optional filters
app.get('/api/products', async (req, res) => {
  try {
    const { category, search } = req.query;
    let query = 'SELECT * FROM products WHERE 1=1';
    const params = [];

    // Filter by category (case-insensitive)
    if (category) {
      query += ' AND LOWER(category) = ?';
      params.push(category.toLowerCase());
    }

    // Filter by search query (case-insensitive search in name and description)
    if (search) {
      query += ' AND (LOWER(name) LIKE ? OR LOWER(description) LIKE ?)';
      const searchParam = `%${search.toLowerCase()}%`;
      params.push(searchParam, searchParam);
    }

    const [rows] = await pool.query(query, params);
    res.json(rows);
  } catch (error) {
    console.error('Fetch products error:', error);
    res.status(500).json({ error: 'Failed to retrieve products from database.' });
  }
});

// GET /api/products/:id - Get product details
app.get('/api/products/:id', async (req, res) => {
  try {
    const productId = parseInt(req.params.id);
    const [rows] = await pool.query('SELECT * FROM products WHERE id = ?', [productId]);

    if (rows.length === 0) {
      return res.status(404).json({ error: 'Product not found' });
    }

    res.json(rows[0]);
  } catch (error) {
    console.error('Fetch product details error:', error);
    res.status(500).json({ error: 'Failed to retrieve product details.' });
  }
});

// POST /api/orders - Create a new order (with stock checking & reduction inside transaction)
app.post('/api/orders', async (req, res) => {
  const { customer_name, customer_email, customer_phone, customer_address, payment_method, items } = req.body;

  // Basic validation
  if (!customer_name || !customer_email || !customer_phone || !customer_address || !payment_method || !items || !Array.isArray(items) || items.length === 0) {
    return res.status(400).json({ error: 'Please provide all required customer info and checkout items.' });
  }

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const itemsToProcess = [];
    let totalAmount = 0;

    // Verify stock and existence of each product
    for (const cartItem of items) {
      const [productRows] = await connection.query('SELECT * FROM products WHERE id = ? FOR UPDATE', [parseInt(cartItem.id)]);
      if (productRows.length === 0) {
        throw new Error(`Product with ID ${cartItem.id} does not exist.`);
      }

      const product = productRows[0];
      const qty = parseInt(cartItem.quantity);
      if (isNaN(qty) || qty <= 0) {
        throw new Error(`Invalid quantity for product ${product.name}.`);
      }

      if (product.stock < qty) {
        throw new Error(`Insufficient stock for ${product.name}. Only ${product.stock} items left.`);
      }

      totalAmount += product.price * qty;
      itemsToProcess.push({
        product,
        quantity: qty,
        price: product.price
      });
    }

    // Insert order into orders table
    const orderId = 'ORD-' + Math.random().toString(36).substr(2, 9).toUpperCase();
    const orderDate = new Date();
    
    await connection.query(
      'INSERT INTO orders (id, customer_name, customer_email, customer_phone, customer_address, payment_method, total_amount, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
      [orderId, customer_name, customer_email, customer_phone, customer_address, payment_method, totalAmount, orderDate]
    );

    // Insert items into order_items and update product stock
    const orderedItems = [];
    for (const item of itemsToProcess) {
      const subtotal = item.price * item.quantity;
      await connection.query(
        'INSERT INTO order_items (order_id, product_id, name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)',
        [orderId, item.product.id, item.product.name, item.quantity, item.price, subtotal]
      );

      const newStock = item.product.stock - item.quantity;
      await connection.query('UPDATE products SET stock = ? WHERE id = ?', [newStock, item.product.id]);

      orderedItems.push({
        product_id: item.product.id,
        name: item.product.name,
        quantity: item.quantity,
        price: item.price,
        subtotal
      });
    }

    await connection.commit();

    res.status(201).json({
      id: orderId,
      customer_name,
      customer_email,
      customer_phone,
      customer_address,
      payment_method,
      items: orderedItems,
      total_amount: totalAmount,
      order_date: orderDate.toISOString()
    });
  } catch (error) {
    await connection.rollback();
    console.error('Order Transaction Error:', error);
    res.status(400).json({ error: error.message || 'Failed to place the order in the database.' });
  } finally {
    connection.release();
  }
});

// GET /api/orders - Get all order history
app.get('/api/orders', async (req, res) => {
  try {
    const [orders] = await pool.query('SELECT * FROM orders ORDER BY order_date DESC');
    
    if (orders.length === 0) {
      return res.json([]);
    }

    // Fetch order items for each order
    const formattedOrders = [];
    for (const order of orders) {
      const [items] = await pool.query('SELECT product_id, name, quantity, price, subtotal FROM order_items WHERE order_id = ?', [order.id]);
      formattedOrders.push({
        id: order.id,
        customer_name: order.customer_name,
        customer_email: order.customer_email,
        customer_phone: order.customer_phone,
        customer_address: order.customer_address,
        payment_method: order.payment_method,
        items: items,
        total_amount: order.total_amount,
        order_date: order.order_date
      });
    }

    res.json(formattedOrders);
  } catch (error) {
    console.error('Fetch orders error:', error);
    res.status(500).json({ error: 'Failed to retrieve order history.' });
  }
});

// POST /api/register - Register a new user
app.post('/api/register', async (req, res) => {
  try {
    const { name, email, password, phone, address } = req.body;

    if (!name || !email || !password || !phone || !address) {
      return res.status(400).json({ error: 'Please provide all details (name, email, password, phone, address).' });
    }

    // Check if email already exists
    const [existingUsers] = await pool.query('SELECT id FROM users WHERE LOWER(email) = ?', [email.toLowerCase()]);
    if (existingUsers.length > 0) {
      return res.status(400).json({ error: 'Email is already registered.' });
    }

    const passwordHash = hashPassword(password);
    const [result] = await pool.query(
      'INSERT INTO users (name, email, password_hash, phone, address) VALUES (?, ?, ?, ?, ?)',
      [name, email, passwordHash, phone, address]
    );

    const newUserProfile = {
      id: result.insertId,
      name,
      email,
      phone,
      address
    };

    res.status(201).json(newUserProfile);
  } catch (error) {
    console.error('Register user error:', error);
    res.status(500).json({ error: 'Failed to create account.' });
  }
});

// POST /api/login - Log in an existing user
app.post('/api/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(400).json({ error: 'Please provide email and password.' });
    }

    const [users] = await pool.query('SELECT * FROM users WHERE LOWER(email) = ?', [email.toLowerCase()]);
    if (users.length === 0) {
      return res.status(401).json({ error: 'Invalid email or password.' });
    }

    const user = users[0];
    const hashedInput = hashPassword(password);
    if (user.password_hash !== hashedInput) {
      return res.status(401).json({ error: 'Invalid email or password.' });
    }

    const newUserProfile = {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      address: user.address
    };

    res.json(newUserProfile);
  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ error: 'Failed to log in.' });
  }
});

app.listen(PORT, () => {
  console.log(`Database server running at http://localhost:${PORT}`);
});
