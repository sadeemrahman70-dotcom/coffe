<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Coffee Store Admin - Dashboard</title>

<style>
  :root{
    --bg:#EFE6DA;            /* page background (light) */
    --panel:#F8F1E7;         /* cards/panels */
    --panel-2:#FAF3EA;       /* topbar lighter */
    --brown:#4A2C1D;         /* sidebar (dark brown) */
    --brown-2:#6A3F28;       /* borders/secondary brown */
    --text:#2E2420;
    --muted:#6B5A50;
    --line:#E2D2BE;
    --shadow: 0 18px 35px rgba(46,36,32,0.12);
    --radius:18px;
  }

  *{box-sizing:border-box}
  body{
    margin:0;
    font-family: Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
  }

 
  .layout{
  display:flex;
  min-height:100vh;
  background: var(--bg);
}

  /* Sidebar */
  .sidebar{
    width: 290px;
    background: var(--brown);
    color:#fff;
    padding: 18px;
  }

  .brand{
    display:flex;
     gap:18px;
    align-items:center;
    padding: 14px;
    border-radius: 16px;
    background: rgba(255,255,255,0.10);
    margin-bottom: 18px;
  }

.brand .logo{
  width:70px;
  height:70px;
  border-radius:50%;      /* هذا يخليه دائرة */
  overflow:hidden;        /* مهم عشان الصورة تلتزم بالدائرة */
  border: 3px solid rgba(255,255,255,0.3);
  background:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
}

.logo img{
  width:100%;
  height:100%;
  object-fit:cover;
  border-radius:12px;
  display:block;
}

.brand h2{
  margin:0;
  font-size:22px;
  font-weight:700;
  line-height:1.1;
}

.brand p{
  margin:4px 0 0;
  font-size:13px;
  opacity:0.9;
color: rgba(255,255,255,0.7);
}

.brand div{
  display:flex;
  flex-direction:column;
  justify-content:center;
}

  .nav{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin-top: 8px;
  }

  .nav a{
    text-decoration:none;
    color:#fff;
    padding: 14px 14px;
    border-radius: 14px;
    background: rgba(255,255,255,0.10);
    display:flex;
    align-items:center;
    justify-content:space-between;
    transition: .18s;
    font-weight: bold;
    font-size: 14px;
  }
  .nav a:hover{
  background: rgba(255,255,255,0.2);
  transform: translateX(4px);
}
  .nav a.active{
    background: rgba(255,255,255,0.20);
  }

  /* Main */
  .main{
    flex:1;
    padding: 18px;
  }

  /* Topbar */
  .topbar{
    background: var(--panel-2);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 14px 16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap: 14px;
    margin-bottom: 16px;
  }

  .title h1{
    margin:0;
    font-size: 20px;
  }
  .title small{
    color: var(--muted);
  }

  .actions{
    display:flex;
    align-items:center;
    gap: 10px;
    flex-wrap:wrap;
  }

  .search{
    width: 340px;
    max-width: 55vw;
    padding: 11px 14px;
    border-radius: 999px;
    border: 1px solid var(--line);
    outline: none;
    background:#fff;
  }

  .btn{
    padding: 10px 14px;
    border-radius: 999px;
    border: 1px solid var(--brown-2);
    background: #fff;
    color: var(--brown);
    font-weight:bold;
    cursor:pointer;
    transition: .18s;
  }
  .btn:hover{ transform: translateY(-1px); }

  .btn.primary{
    background: var(--brown);
    border-color: var(--brown);
    color:#fff;
  }

  /* KPI cards */
  .cards{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
  }

  .card{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 16px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    min-height: 92px;
  }
  .card .meta small{ color: var(--muted); }
  .card .meta h3{
    margin: 8px 0 0;
    font-size: 26px;
  }
  .chip{
    padding: 10px 14px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: rgba(74,44,29,0.07);
    color: var(--brown);
    font-weight:bold;
    font-size: 13px;
    white-space:nowrap;
  }

  /* Grid content */
  .grid{
    display:grid;
    grid-template-columns: 1.65fr 1fr;
    gap: 14px;
  }

  .panel{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 16px;
  }

  .panel h2{
    margin: 0 0 14px;
    font-size: 18px;
  }

  /* Table */
  table{
    width:100%;
    border-collapse:collapse;
    font-size: 14px;
  }

  th, td{
    text-align:left;
    padding: 12px 10px;
    border-bottom: 1px solid var(--line);
  }
  th{
    color: var(--muted);
    font-weight: bold;
  }

  .status{
    display:inline-block;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: bold;
    font-size: 13px;
    border: 1px solid transparent;
  }

  .pending{ background:#FFF1D6; color:#8A5A12; border-color:#F2D6A5; }
  .preparing{ background:#E8F0FF; color:#234E91; border-color:#CFE0FF; }
  .done{ background:#E8FFF2; color:#1E6B3A; border-color:#BFEED2; }

  .mini-btn{
    border: 1px solid var(--brown-2);
    background: #fff;
    color: var(--brown);
    padding: 8px 12px;
    border-radius: 12px;
    cursor:pointer;
    font-weight:bold;
  }
  .mini-btn:hover{ background: rgba(74,44,29,0.06); }

  /* Quick actions */
  .qa{
    display:grid;
    gap: 14px;
  }

  .qa-box{
    border: 1px dashed rgba(74,44,29,0.40);
    border-radius: 18px;
    background: #fff;
    padding: 14px;
  }
  .qa-box h3{
    margin:0 0 6px;
    font-size: 16px;
  }
  .qa-box p{
    margin:0 0 12px;
    color: var(--muted);
  }

  .row{
    display:flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  /* Responsive */
  @media (max-width: 1100px){
    .cards{ grid-template-columns: repeat(2, 1fr); }
    .grid{ grid-template-columns: 1fr; }
    .search{ width: 100%; max-width: 100%; }
  }
  @media (max-width: 780px){
    .layout{ flex-direction:column; }
    .sidebar{ width:100%; }
    .cards{ grid-template-columns: 1fr; }
    .topbar{ flex-direction:column; align-items:stretch; }
    .actions{ justify-content: space-between; }
  }

/* (اختياري) hover بدون ما يخرب المقاسات */
.nav-parent:hover,
.submenu .sub:hover{
  background: rgba(255,255,255,0.16);
}
/* ---- Dropdown (CSS only) ---- */
.nav .has-sub{
  position: relative;
}

.nav input[type="checkbox"]{
  display:none;
}

/* نفس شكل الروابط */
.nav .nav-link{
  text-decoration:none;
  color:#fff;
  padding: 14px 14px;
  border-radius: 14px;
  background: rgba(255,255,255,0.10);
  display:flex;
  align-items:center;
  justify-content:space-between;
  transition: .18s;
  font-weight: bold;
  font-size: 14px;
  cursor:pointer;
}

.nav .nav-link:hover{
  background: rgba(255,255,255,0.16);
  transform: translateX(2px);
}

/* سهم يتحرك */
.nav .chev{
  transition:.2s;
  opacity:.9;
}

/* القائمة الفرعية */
.submenu{
  display:none;
  margin: 8px 0 0 0;
  padding: 10px;
  border-radius: 14px;
  background: rgba(255,255,255,0.08);
}

/* إظهار submenu عند التفعيل */
.nav input:checked ~ .submenu{
  display:grid;
  gap:10px;
}

/* تدوير السهم عند الفتح */
.nav input:checked + label .chev{
  transform: rotate(90deg);
}

/* روابط submenu */
.submenu a{
  text-decoration:none;
  color:#fff;
  padding: 12px 12px;
  border-radius: 12px;
  background: rgba(255,255,255,0.10);
  font-size: 13px;
  font-weight: 600;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.submenu a:hover{
  background: rgba(255,255,255,0.16);
}


</style>
</head>

<body>
      <div class="layout">


      <!-- Sidebar -->
      <aside class="sidebar">
  <div class="brand">
  <div class="logo">
    <img src="Brew&Bean3.jpg" alt="Logo" >

  </div>
  <div>
    <h2>Brew & Bean</h2>
    <p>Admin Panel</p>
  </div>
</div>

        <nav class="nav">
          <a class="active" href="#">Dashboard <span>›</span></a>
          <a href="#">Manage Products <span>›</span></a>
          <a href="#">Manage Brewing Methods <span>›</span></a>
          <a href="#">Manage Orders <span>›</span></a>
          <a href="#">Customers <span>›</span></a>
          <a href="#">Logout <span>⎋</span></a>
        </nav>
      </aside>

      <!-- Main -->
      <main class="main">

        <!-- Topbar -->
        <div class="topbar">
          <div class="title">
            <h1>Admin Dashboard</h1>
            <small>Overview of orders, products, and store performance</small>
          </div>

          <div class="actions">
            <input class="search" type="text" placeholder="Search orders / products..." />
            <button class="btn" type="button">Export</button>
            <button class="btn primary" type="button">New Product</button>
          </div>
        </div>

        <!-- KPI -->
        <section class="cards">
          <div class="card">
            <div class="meta">
              <small>Total Orders</small>
              <h3>128</h3>
            </div>
            <div class="chip">This month</div>
          </div>

          <div class="card">
            <div class="meta">
              <small>Pending Orders</small>
              <h3>12</h3>
            </div>
            <div class="chip">Need action</div>
          </div>

          <div class="card">
            <div class="meta">
              <small>Completed Orders</small>
              <h3>96</h3>
            </div>
            <div class="chip">Delivered</div>
          </div>

          <div class="card">
            <div class="meta">
              <small>Total Products</small>
              <h3>24</h3>
            </div>
            <div class="chip">Active</div>
          </div>
        </section>

        <!-- Content -->
        <section class="grid">

          <!-- Recent Orders -->
          <div class="panel">
            <h2>Recent Orders</h2>

            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td>#10021</td>
                  <td>Sarah A.</td>
                  <td>2026-03-01</td>
                  <td>85 SAR</td>
                  <td><span class="status pending">Pending</span></td>
                  <td><button class="mini-btn" type="button">View</button></td>
                </tr>

                <tr>
                  <td>#10020</td>
                  <td>Reem M.</td>
                  <td>2026-02-28</td>
                  <td>120 SAR</td>
                  <td><span class="status preparing">Preparing</span></td>
                  <td><button class="mini-btn" type="button">Update</button></td>
                </tr>

                <tr>
                  <td>#10019</td>
                  <td>Fahad S.</td>
                  <td>2026-02-27</td>
                  <td>60 SAR</td>
                  <td><span class="status done">Completed</span></td>
                  <td><button class="mini-btn" type="button">Receipt</button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Quick Actions -->
          <div class="panel">
            <h2>Quick Actions</h2>

            <div class="qa">
              <div class="qa-box">
                <h3>Add New Product</h3>
                <p>Create a new coffee item (beans, capsules, tools, etc.).</p>
                <div class="row">
                  <button class="btn primary" type="button">Add Product</button>
                  <button class="btn" type="button">Manage Products</button>
                </div>
              </div>

              <div class="qa-box">
                <h3>Manage Orders</h3>
                <p>Approve, prepare, and complete customer orders.</p>
                <div class="row">
                  <button class="btn primary" type="button">View Orders</button>
                  <button class="btn" type="button">Pending Only</button>
                </div>
              </div>
            </div>

          </div>
        </section>

      </main>
  </div>
</body>
</html>