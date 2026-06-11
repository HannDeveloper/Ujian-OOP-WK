<?php
class User {
    protected $nama, $noHP;
    public function __construct($nama, $noHP) {
        if (empty($nama)) throw new Exception("Nama tidak boleh kosong!");
        if (strlen($noHP) < 10) throw new Exception("Nomor HP minimal 10 digit!");
        $this->nama = $nama; $this->noHP = $noHP;
    }
    public function getNama() { return $this->nama; }
    public function getStatus() { return "User"; }
}

class Pelanggan extends User {
    private $poin = 0;
    public function getStatus() { return "Pelanggan"; }
    public function tambahPoin($total) { $this->poin += floor($total / 10000); }
    public function getPoin() { return $this->poin; }
}

class Layanan {
    private $jenis, $tarif;
    private $daftar = ["goRide Reguler"=>2500,"goRide Prioritas"=>3000,"goCar"=>4500,"goCar XL"=>6000,"goFood"=>2000];
    // cek apakah kosong atau tidak
    public function __construct($jenis) {
        if (!isset($this->daftar[$jenis])) throw new Exception("Layanan tidak valid!");
        $this->jenis = $jenis; $this->tarif = $this->daftar[$jenis];
    }
    public function getTarif() { return $this->tarif; }
    public function getJenis() { return $this->jenis; }
}

class Voucher {
    private $diskon;
    private $daftar = ["HEMAT10"=>10,"HEMAT20"=>20,"HEMAT30"=>30];
    public function __construct($kode) {
        if (!isset($this->daftar[$kode])) throw new Exception("Voucher tidak valid!");
        $this->diskon = $this->daftar[$kode];
    }
    public function hitungDiskon($s) {
        return $s * ($this->diskon / 100);
        }
}

class Pembayaran {
    public function getMethod() {
        return "Umum";
        }
    public function getAdmin()  {
        return 0;
        }
}
class eWallet extends Pembayaran {
    public function getMethod() {
        return "eWallet";
        }
    public function getAdmin()  {
        return 1000;
        }
}
class TransferBank extends Pembayaran {
    public function getMethod() {
        return "Transfer Bank";
        }
    public function getAdmin()  {
        return 2500;
        }
}
class Cash extends Pembayaran {
    public function getMethod() {
        return "Cash";
        }
}

class Transaksi {
    private $pelanggan, $layanan, $pembayaran, $voucher, $jarak;
    private static $totalTransaksi = 0;

    public function __construct($pelanggan, $layanan, $pembayaran, $voucher, $jarak) {
        if ($jarak <= 0) throw new Exception("Jarak harus lebih dari 0!");
        $this->pelanggan = $pelanggan;
        $this->layanan = $layanan;
        $this->pembayaran = $pembayaran;
        $this->voucher = $voucher;
        $this->jarak = $jarak;
        self::$totalTransaksi++;
    }

    public function hitungSubtotal()      {
        return $this->jarak * $this->layanan->getTarif();
        }
    public function hitungDiskonMember($s){
        return ($s > 50000) ? $s * 0.05 : 0;
        }
    public function hitungDiskonVoucher($s){
        return $this->voucher ? $this->voucher->hitungDiskon($s) : 0;
        }
    public function hitungAdmin() {
        return $this->pembayaran->getAdmin();
        }

    public function hitungTotal() {
        $s = $this->hitungSubtotal();
        $dm = $this->hitungDiskonMember($s);
        $dv = $this->hitungDiskonVoucher($s);
        $admin = $this->hitungAdmin();
        $total = $s - $dm - $dv + $admin;
        $this->pelanggan->tambahPoin($total);
        return ["subtotal"=>$s,"dm"=>$dm,"dv"=>$dv,"admin"=>$admin,"total"=>$total];
    }

    public static function getTotalTransaksi() { return self::$totalTransaksi; }
}

// proses
$error = ""; $result = null;

// dipakai saat mengakses halaman GET, POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $layanan = new Layanan($_POST['layanan']);
        if ($_POST['pembayaran'] == "eWallet")           $pm = new eWallet();
        elseif ($_POST['pembayaran'] == "Transfer Bank") $pm = new TransferBank();
        else                                             $pm = new Cash();
        // ternary operator
        $voucher   = !empty($_POST['voucher']) ? new Voucher($_POST['voucher']) : null;
        $pelanggan = new Pelanggan($_POST['nama'], $_POST['noHP']);
        $transaksi = new Transaksi($pelanggan, $layanan, $pm, $voucher, (int)$_POST['jarak']);
        $result    = $transaksi->hitungTotal();
        $result   += ["nama"=>$pelanggan->getNama(),"status"=>$pelanggan->getStatus(),
                      "noHP"=>$_POST['noHP'],"layanan"=>$layanan->getJenis(),
                      "jarak"=>(int)$_POST['jarak'],"metode"=>$pm->getMethod(),
                      "poin"=>$pelanggan->getPoin(),"transaksi"=>Transaksi::getTotalTransaksi()];
    } catch (Exception $e) { $error = $e->getMessage(); }
}

function rp($n) { return "Rp" . number_format($n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Simulasi aplikasi ojek online dengan berbagai layanan transportasi dan pengiriman">
    <title>Ojek Online — Simulasi Transaksi</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<header>
    <div class="header-inner">
        <i class="fas fa-motorcycle"></i>
        <div>
            <div>Simulasi Ojek Online</div>
            <div class="header-subtitle">Layanan Transportasi & Pengiriman Terpercaya</div>
        </div>
    </div>
</header>

<div class="container">

    <?php if ($error != "") { ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php } ?>

    <div class="main-layout">
        <!-- KOLOM KIRI: Form Transaksi -->
        <div class="col-left">
            <div class="card">
                <h2><i class="fas fa-file-invoice"></i> Form Transaksi</h2>
                <form method="post">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nama</label>
                        <div class="input-wrapper">
                            <input type="text" name="nama" placeholder="Masukkan nama lengkap">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> No HP</label>
                        <div class="input-wrapper">
                            <input type="text" name="noHP" placeholder="Contoh: 08123456789">
                            <i class="fas fa-phone"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-road"></i> Jarak (km)</label>
                        <div class="input-wrapper">
                            <input type="number" name="jarak" min="1" placeholder="Masukkan jarak tempuh">
                            <i class="fas fa-road"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-list"></i> Layanan</label>
                        <div class="input-wrapper">
                            <select name="layanan">
                                <option>goRide Reguler</option>
                                <option>goRide Prioritas</option>
                                <option>goCar</option>
                                <option>goCar XL</option>
                                <option>goFood</option>
                            </select>
                            <i class="fas fa-list"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-ticket-alt"></i> Voucher</label>
                        <div class="input-wrapper">
                            <select name="voucher">
                                <option value="">-- Tidak Ada --</option>
                                <option>HEMAT10</option>
                                <option>HEMAT20</option>
                                <option>HEMAT30</option>
                            </select>
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-credit-card"></i> Pembayaran</label>
                        <div class="input-wrapper">
                            <select name="pembayaran">
                                <option>eWallet</option>
                                <option>Transfer Bank</option>
                                <option>Cash</option>
                            </select>
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Proses Transaksi
                    </button>
                </form>
            </div>
        </div>

        <!-- KOLOM KANAN: Deskripsi Ojek Online -->
        <div class="col-right">
            <div class="desc-panel">
                <h2><i class="fas fa-info-circle"></i> Tentang Ojek Online</h2>
                <p>
                    Selamat datang di platform simulasi ojek online! Kami menyediakan berbagai layanan transportasi
                    dan pengiriman yang cepat, aman, dan terpercaya untuk memenuhi kebutuhan mobilitas Anda.
                </p>

                <div class="desc-highlight">
                    <h3><i class="fas fa-star"></i> Layanan Unggulan</h3>
                    <p>Nikmati berbagai pilihan layanan dengan tarif terjangkau dan promo menarik setiap harinya.</p>
                </div>

                <h3 style="font-size:14px; color:#1a7f37; margin-bottom:12px; font-weight:700;">
                    <i class="fas fa-th-list" style="color:#27ae60; margin-right:6px;"></i> Daftar Layanan & Tarif
                </h3>
                <ul class="service-list">
                    <li>
                        <i class="fas fa-motorcycle"></i>
                        <div class="service-info">
                            <div class="service-name">goRide Reguler</div>
                            <div class="service-price">Rp2.500 / km</div>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-motorcycle"></i>
                        <div class="service-info">
                            <div class="service-name">goRide Prioritas</div>
                            <div class="service-price">Rp3.000 / km</div>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-car"></i>
                        <div class="service-info">
                            <div class="service-name">goCar</div>
                            <div class="service-price">Rp4.500 / km</div>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-van-shuttle"></i>
                        <div class="service-info">
                            <div class="service-name">goCar XL</div>
                            <div class="service-price">Rp6.000 / km</div>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-utensils"></i>
                        <div class="service-info">
                            <div class="service-name">goFood</div>
                            <div class="service-price">Rp2.000 / km</div>
                        </div>
                    </li>
                </ul>

                <div class="desc-highlight">
                    <h3><i class="fas fa-tags"></i> Promo Voucher</h3>
                    <p>Gunakan kode voucher <strong>HEMAT10</strong>, <strong>HEMAT20</strong>, atau <strong>HEMAT30</strong> untuk mendapatkan diskon hingga 30%!</p>
                </div>

                <div class="desc-highlight">
                    <h3><i class="fas fa-coins"></i> Poin Reward</h3>
                    <p>Setiap transaksi Rp10.000, Anda mendapatkan 1 poin reward. Kumpulkan poin untuk keuntungan lebih!</p>
                </div>

                <div class="feature-badges">
                    <span class="badge"><i class="fas fa-shield-halved"></i> Aman</span>
                    <span class="badge"><i class="fas fa-bolt"></i> Cepat</span>
                    <span class="badge"><i class="fas fa-clock"></i> 24 Jam</span>
                    <span class="badge"><i class="fas fa-wallet"></i> Cashless</span>
                    <span class="badge"><i class="fas fa-headset"></i> Support</span>
                </div>
            </div>
        </div>
    </div>

    <!-- STRUK PEMBAYARAN (full width below) -->
    <?php if ($result != null) { ?>
    <div class="struk">
        <h2><i class="fas fa-receipt"></i> Struk Pembayaran</h2>
        <table>
            <tr><td><i class="fas fa-user"></i> Nama</td><td><?php echo $result['nama']; ?></td></tr>
            <tr><td><i class="fas fa-id-badge"></i> Status</td><td><?php echo $result['status']; ?></td></tr>
            <tr><td><i class="fas fa-phone"></i> No HP</td><td><?php echo $result['noHP']; ?></td></tr>
            <tr><td><i class="fas fa-concierge-bell"></i> Layanan</td><td><?php echo $result['layanan']; ?></td></tr>
            <tr><td><i class="fas fa-route"></i> Jarak</td><td><?php echo $result['jarak']; ?> km</td></tr>
            <tr><td><i class="fas fa-calculator"></i> Subtotal</td><td><?php echo rp($result['subtotal']); ?></td></tr>
            <tr><td><i class="fas fa-percent"></i> Diskon Member (5%)</td><td>- <?php echo rp($result['dm']); ?></td></tr>
            <tr><td><i class="fas fa-ticket-alt"></i> Diskon Voucher</td><td>- <?php echo rp($result['dv']); ?></td></tr>
            <tr><td><i class="fas fa-hand-holding-dollar"></i> Biaya Admin (<?php echo $result['metode']; ?>)</td><td>+ <?php echo rp($result['admin']); ?></td></tr>
            <tr class="baris-total"><td><i class="fas fa-money-bill-wave"></i> Total Bayar</td><td><?php echo rp($result['total']); ?></td></tr>
            <tr><td><i class="fas fa-award"></i> Poin Reward</td><td><?php echo $result['poin']; ?> poin</td></tr>
            <tr><td><i class="fas fa-chart-line"></i> Total Transaksi Sistem</td><td><?php echo $result['transaksi']; ?></td></tr>
        </table>
    </div>
    <?php } ?>

</div>

<div class="footer">
    <i class="fas fa-motorcycle"></i> Ojek Online Simulator &copy; 2026 — Dibuat dengan senang hati</i>
</div>

</body>
</html>