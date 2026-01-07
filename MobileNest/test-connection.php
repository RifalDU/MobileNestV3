<?php
// Include file konfigurasi
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Test Database Connection - MobileNest</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo ".success { color: #28a745; }";
echo ".error { color: #dc3545; }";
echo ".info-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }";
echo "table { width: 100%; border-collapse: collapse; }";
echo "table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "table th { background-color: #f8f9fa; font-weight: bold; }";
echo "</style>";
echo "</head>";
echo "<body>";

// Test koneksi
if ($conn) {
    echo "<div class='info-box'>";
    echo "<h2 class='success'>✅ Koneksi Database Berhasil!</h2>";
    echo "<p><strong>Server:</strong> " . htmlspecialchars($db_host) . "</p>";
    echo "<p><strong>Database:</strong> " . htmlspecialchars($db_name) . "</p>";
    echo "<p><strong>User:</strong> " . htmlspecialchars($db_user) . "</p>";
    echo "</div>";
    
    // Test query - ambil semua tabel
    echo "<div class='info-box'>";
    echo "<h3>Daftar Tabel di Database:</h3>";
    $sql_tables = "SHOW TABLES";
    $result_tables = mysqli_query($conn, $sql_tables);
    
    if ($result_tables) {
        echo "<table>";
        echo "<thead><tr><th>No</th><th>Nama Tabel</th><th>Total Rows</th></tr></thead>";
        echo "<tbody>";
        
        $no = 1;
        while ($row_table = mysqli_fetch_row($result_tables)) {
            $table_name = $row_table[0];
            
            // Count rows di setiap tabel
            $count_sql = "SELECT COUNT(*) as total FROM " . mysqli_real_escape_string($conn, $table_name);
            $count_result = mysqli_query($conn, $count_sql);
            $count_row = mysqli_fetch_assoc($count_result);
            $total_rows = $count_row['total'] ?? 0;
            
            echo "<tr>";
            echo "<td>" . $no . "</td>";
            echo "<td><strong>" . htmlspecialchars($table_name) . "</strong></td>";
            echo "<td>" . $total_rows . " rows</td>";
            echo "</tr>";
            $no++;
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Gagal mengambil daftar tabel.</p>";
    }
    echo "</div>";
    
    // Test query spesifik - produk
    echo "<div class='info-box'>";
    echo "<h3>Data Produk:</h3>";
    $sql = "SELECT COUNT(*) as total FROM produk";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p><strong>Total Produk:</strong> <span class='success'>" . $row['total'] . " produk</span></p>";
        
        // Ambil 5 produk pertama
        if ($row['total'] > 0) {
            $sql_produk = "SELECT id_produk, nama_produk, merek, harga, stok FROM produk LIMIT 5";
            $result_produk = mysqli_query($conn, $sql_produk);
            
            if ($result_produk && mysqli_num_rows($result_produk) > 0) {
                echo "<h4>Sample 5 Produk Pertama:</h4>";
                echo "<table>";
                echo "<thead><tr><th>ID</th><th>Nama Produk</th><th>Merek</th><th>Harga</th><th>Stok</th></tr></thead>";
                echo "<tbody>";
                
                while ($prod = mysqli_fetch_assoc($result_produk)) {
                    echo "<tr>";
                    echo "<td>" . $prod['id_produk'] . "</td>";
                    echo "<td>" . htmlspecialchars($prod['nama_produk']) . "</td>";
                    echo "<td>" . htmlspecialchars($prod['merek'] ?? '-') . "</td>";
                    echo "<td>Rp " . number_format($prod['harga'], 0, ',', '.') . "</td>";
                    echo "<td>" . $prod['stok'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</tbody>";
                echo "</table>";
            }
        }
    } else {
        echo "<p class='error'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
    echo "</div>";
    
    // Test query - users
    echo "<div class='info-box'>";
    echo "<h3>Data Users:</h3>";
    $sql_users = "SELECT COUNT(*) as total_users FROM users";
    $result_users = mysqli_query($conn, $sql_users);
    
    if ($result_users) {
        $row_users = mysqli_fetch_assoc($result_users);
        echo "<p><strong>Total Users:</strong> <span class='success'>" . $row_users['total_users'] . " users</span></p>";
    } else {
        echo "<p class='error'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
    echo "</div>";
    
    // Test query - admin
    echo "<div class='info-box'>";
    echo "<h3>Data Admin:</h3>";
    $sql_admin = "SELECT COUNT(*) as total_admin FROM admin";
    $result_admin = mysqli_query($conn, $sql_admin);
    
    if ($result_admin) {
        $row_admin = mysqli_fetch_assoc($result_admin);
        echo "<p><strong>Total Admin:</strong> <span class='success'>" . $row_admin['total_admin'] . " admin</span></p>";
    } else {
        echo "<p class='error'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
    echo "</div>";
    
    // Success message
    echo "<div class='info-box' style='background-color: #d4edda; border: 1px solid #c3e6cb;'>";
    echo "<h3 class='success'>✅ Setup Development Selesai!</h3>";
    echo "<p>Aplikasi MobileNest siap untuk dikembangkan.</p>";
    echo "<p><strong>Langkah selanjutnya:</strong></p>";
    echo "<ul>";
    echo "<li>Test Register: <a href='user/register.php'>user/register.php</a></li>";
    echo "<li>Test Login: <a href='user/login.php'>user/login.php</a></li>";
    echo "<li>Test List Produk: <a href='produk/list-produk.php'>produk/list-produk.php</a></li>";
    echo "<li>Admin Panel: <a href='admin/kelola-produk.php'>admin/kelola-produk.php</a></li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<div class='info-box' style='background-color: #f8d7da; border: 1px solid #f5c6cb;'>";
    echo "<h2 class='error'>❌ Koneksi Database Gagal!</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars(mysqli_connect_error()) . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Pastikan MySQL running di XAMPP Control Panel</li>";
    echo "<li>Pastikan database 'mobilenest_db' sudah dibuat</li>";
    echo "<li>Cek config.php (db_host, db_user, db_pass, db_name)</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</body>";
echo "</html>";

// Tutup koneksi
if ($conn) {
    mysqli_close($conn);
}
?>
