<?php
/**
 * โปรแกรมเช็คชื่อ แสดงพิกัดและเวลา (Version 2.0 Optimized)
 */
header('Content-Type: text/html; charset=UTF-8');
date_default_timezone_set('Asia/Bangkok'); // ตั้งค่า Timezone ให้ตรงกับไทย

// ฟังก์ชันตรวจสอบชื่อ
function validateName($name) {
    $name = trim($name);
    if (empty($name)) return [false, "กรุณากรอกชื่อ"];
    if (mb_strlen($name, 'UTF-8') < 2) return [false, "ชื่อสั้นเกินไป"];
    if (mb_strlen($name, 'UTF-8') > 50) return [false, "ชื่อยาวเกินไป"];
    return [true, "ตรวจสอบข้อมูลเรียบร้อย"];
}

$name = $_POST['name'] ?? '';
$lat = $_POST['lat'] ?? '';
$lon = $_POST['lon'] ?? '';
$submissionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    list($isValid, $message) = validateName($name);
    $submissionResult = [
        'success' => $isValid,
        'message' => $message,
        'time' => date('d/m/Y H:i:s'),
        'lat' => filter_var($lat, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        'lon' => filter_var($lon, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบเช็คชื่ออัจฉริยะ</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f3f4f6;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                              radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1f2937;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        h1 { text-align: center; font-weight: 600; margin-bottom: 0.5rem; color: #111827; }
        p.desc { text-align: center; color: #6b7280; font-size: 0.9rem; margin-bottom: 2rem; }

        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 400; }
        
        input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        button:disabled { opacity: 0.5; cursor: not-allowed; }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-top: 1.5rem;
            border-left: 5px solid;
        }

        .alert-success { background: #ecfdf5; border-color: var(--success); color: #065f46; }
        .alert-danger { background: #fef2f2; border-color: var(--danger); color: #991b1b; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 1rem;
        }

        .info-card {
            background: #f9fafb;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .info-label { color: #6b7280; display: block; }
        .info-value { font-weight: 600; color: #111827; }

        #status-text { font-size: 0.8rem; text-align: center; margin-top: 10px; display: block; }

        .btn-map {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="card">
    <h1>ลงชื่อเข้าใช้</h1>
    <p class="desc">ระบบจะบันทึกเวลาและพิกัดปัจจุบันของคุณ</p>

    <form id="checkinForm" method="POST">
        <div class="form-group">
            <label for="name">ชื่อ-นามสกุล</label>
            <input type="text" id="name" name="name" placeholder="ระบุชื่อของคุณ" required 
                   value="<?php echo htmlspecialchars($name); ?>">
        </div>

        <input type="hidden" id="lat" name="lat">
        <input type="hidden" id="lon" name="lon">

        <button type="submit" id="submitBtn" disabled>กำลังดึงพิกัด GPS...</button>
        <span id="status-text">กรุณาอนุญาตการเข้าถึงพิกัดเพื่อทำรายการ</span>
    </form>

    <?php if ($submissionResult): ?>
        <div class="alert <?php echo $submissionResult['success'] ? 'alert-success' : 'alert-danger'; ?>">
            <strong><?php echo $submissionResult['success'] ? '✅ สำเร็จ' : '❌ ผิดพลาด'; ?>:</strong> 
            <?php echo htmlspecialchars($submissionResult['message']); ?>

            <?php if ($submissionResult['success']): ?>
                <div class="info-grid">
                    <div class="info-card">
                        <span class="info-label">เวลาบันทึก</span>
                        <span class="info-value"><?php echo $submissionResult['time']; ?></span>
                    </div>
                    <div class="info-card">
                        <span class="info-label">พิกัด</span>
                        <span class="info-value"><?php echo $submissionResult['lat'] ?: 'N/A'; ?>, <?php echo $submissionResult['lon'] ?: 'N/A'; ?></span>
                    </div>
                </div>
                <?php if($submissionResult['lat']): ?>
                    <a href="https://www.google.com/maps?q=<?php echo $submissionResult['lat']; ?>,<?php echo $submissionResult['lon']; ?>" 
                       target="_blank" class="btn-map">📍 ดูบนแผนที่ Google Maps</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // ฟังก์ชันดึงพิกัดด้วยเบราว์เซอร์ (แม่นยำกว่า IP)
    function getLocation() {
        const btn = document.getElementById('submitBtn');
        const status = document.getElementById('status-text');

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('lat').value = position.coords.latitude;
                    document.getElementById('lon').value = position.coords.longitude;
                    btn.disabled = false;
                    btn.innerText = "บันทึกชื่อและพิกัด";
                    status.innerText = "📍 ระบุตำแหน่งสำเร็จ";
                    status.style.color = "green";
                },
                (error) => {
                    btn.disabled = false;
                    btn.innerText = "บันทึก (โดยไม่ใช้พิกัด)";
                    status.innerText = "⚠️ ไม่สามารถเข้าถึงพิกัดได้: " + error.message;
                    status.style.color = "red";
                },
                { enableHighAccuracy: true, timeout: 5000 }
            );
        } else {
            status.innerText = "เบราว์เซอร์ไม่รองรับ GPS";
            btn.disabled = false;
        }
    }

    // เรียกทำงานทันทีที่โหลดหน้าเว็บ
    window.onload = getLocation;
</script>

</body>
</html>