<h3>Main</h3>
<nav class="admin-nav">
    <a href="/work_folder/realRealestate/admin/index.php"><span class="nav-icon">&#9679;</span> Dashboard</a>
    <a href="/work_folder/realRealestate/admin/spaces/index.php"
        class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/spaces') !== false ? 'active' : '' ?>"><span
            class="nav-icon">&#127970;</span> Spaces</a>
    <a href="/work_folder/realRealestate/admin/visit-requests/index.php"><span class="nav-icon">&#128197;</span> Visit
        Requests</a>
</nav>
<h3>Management</h3>
<nav class="admin-nav">
    <a href="/work_folder/realRealestate/admin/customers/index.php"><span class="nav-icon">&#128101;</span>
        Customers</a>
    <a href="/work_folder/realRealestate/admin/leases/index.php"><span class="nav-icon">&#128196;</span> Leases</a>
    <a href="/work_folder/realRealestate/admin/payments/index.php"><span class="nav-icon">&#128176;</span> Payments</a>
</nav>
<h3>Content</h3>
<nav class="admin-nav">
    <a href="/work_folder/realRealestate/admin/testimonials/index.php"><span class="nav-icon">&#11088;</span>
        Testimonials</a>
    <a href="/work_folder/realRealestate/admin/users/index.php"><span class="nav-icon">&#128272;</span> Users</a>
</nav>
<h3>System</h3>
<nav class="admin-nav">
    <a href="/work_folder/realRealestate/admin/policies.php"><span class="nav-icon">&#9878;</span> Policy Engine</a>
    <a href="/work_folder/realRealestate/admin/audit.php"><span class="nav-icon">&#128214;</span> Audit Log</a>
    <a href="/work_folder/realRealestate/index.php"><span class="nav-icon">&#127968;</span> View Site</a>
</nav>