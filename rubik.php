<?php
// تنظیمات دیتابیس
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rubik_db";

// ایجاد اتصال به MySQL
$conn = new mysqli($servername, $username, $password);

// بررسی خطای اتصال
if ($conn->connect_error) {
    die("خطا در اتصال به دیتابیس: " . $conn->connect_error);
}

// ایجاد دیتابیس اگر وجود ندارد
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// ایجاد جدول اگر وجود ندارد
$sql_table = "CREATE TABLE IF NOT EXISTS records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    record_time VARCHAR(20),
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql_table);

// دریافت و ذخیره داده‌ها
if (isset($_POST['name']) && isset($_POST['time'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $time = $conn->real_escape_string($_POST['time']);

    $sql = "INSERT INTO records (name, record_time) VALUES ('$name', '$time')";
    
    // اصلاح اصلی در این قسمت است:
    if ($conn->query($sql) === TRUE) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>داشبورد پیشرفته روبیک - V3.9</title>
    <!-- کتابخانه‌های Three.js و Chart.js -->
    <script src="../../javaScript/three.min.js"></script>
    <script src="../../javaScript/chart.js"></script>
    <style>
    :root {
        --bg: #121212;
        --panel-bg: #1e1e1e;
        --accent: #ffcc00;
        --text: #fff;
    }

    body {
        font-family: Tahoma, sans-serif;
        background: var(--bg);
        color: var(--text);
        margin: 0;
        padding: 15px;
    }

    h1,
    h3 {
        margin: 0 0 10px 0;
        color: var(--accent);
    }

    .dashboard {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 15px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .card {
        background: var(--panel-bg);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #333;
        margin-bottom: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    }

    /* مکعب و تایمر */
    #cube-container {
        width: 100%;
        height: 350px;
        background: #000;
        border-radius: 6px;
        position: relative;
        overflow: hidden;
    }

    .timer-box {
        text-align: center;
        font-size: 2.2em;
        font-weight: bold;
        font-family: monospace;
        color: #0051ff;
        margin: 10px 0;
        background: #111;
        padding: 10px;
        border-radius: 5px;
    }

    .btn-group {
        display: flex;
        gap: 5px;
        justify-content: center;
        margin-bottom: 10px;
    }

    button {
        padding: 8px 15px;
        cursor: pointer;

        border: none;
        border-radius: 4px;
        font-weight: bold;
        color: #000;
    }

    button:hover {
        opacity: 0.9;
    }

    /* جدول و پنل‌ها */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85em;
    }

    th,
    td {
        border-bottom: 1px solid #444;
        padding: 6px;
        text-align: center;
    }

    th {
        background: #252525;
    }

    /* نمودار */
    </style>
</head>

<body>

    <h1>داشبورد وب‌محور روبیک (نسخه V3.9)</h1>

    <div class="dashboard">
        <!-- ستون سمت چپ: گرافیک Three.js، تایمر و نمودار پیشرفت -->
        <div class="main-column">
            <div class="card">
                <h3>مکعب سه‌بعدی تعاملی</h3>
                <div id="cube-container"></div>
            </div>

            <div class="card">
                <h3>تایمر هوشمند رکوردگیری</h3>
                <div class="timer-box" id="timerDisplay">00:00.00</div>
                <div class="btn-group">
                    <button onclick="startTimer()" style="        padding: 8px 15px;
        cursor: pointer;
        background: green;
        border: none;
        border-radius: 4px;
        font-weight: bold;
        color: #ffffff;">شروع</button>
                    <button onclick="stopTimer()" style="background:red; color:#fff;">توقف و ذخیره</button>
                    <button onclick="resetTimer()" style="background:blue; color:#fff;">بازنشانی</button>
                </div>
            </div>

            <div class="card" style="display: none;">
                <h3>نمودار روند رکوردها</h3>
                <div class="chart-container">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ستون سمت راست: دیتابیس، تاریخچه و دانستنی‌ها -->
        <div class="side-column">
            <div class="card">
                <h3>📜 ثبت رکوردها در دیتابیس</h3>
                <form id="recordForm" onsubmit="saveRecordToDB(event)">
                    <div style="margin-bottom: 8px;">
                        <label>نام کاربر:</label><br>
                        <input type="text" id="userName" required
                            style="width: 100%; padding: 5px; background: #222; border: 1px solid #444; color: #fff; border-radius: 4px;"
                            placeholder="نام خودتان را بنویسید">
                    </div>
                    <div style="margin-bottom: 8px;">
                        <label>زمان ثبت شده (ثانیه):</label><br>
                        <input type="text" id="recordTime" readonly
                            style="width: 100%; padding: 5px; background: #222; border: 1px solid #444; color: #006fff; border-radius: 4px; font-weight: bold;"
                            placeholder="00:00.00">
                    </div>
                    <button type="submit" style="width: 100%; background: blue; color: #fff;">ذخیره در دیتابیس
                        (PHP)</button>
                </form>
                <div id="saveStatus" style="font-size: 0.8em; margin-top: 5px; text-align: center; color: #ffcc00;">
                </div>
            </div>

            <div class="card">
                <h3>تاریخچه رکوردهای جهانی</h3>
                <table>
                    <tr>
                        <th>سال</th>
                        <th>نام</th>
                        <th>زمان</th>
                    </tr>
                    <tr>
                        <td>1982</td>
                        <td>Minh Thai</td>
                        <td>22.95s</td>
                    </tr>
                    <tr>
                        <td>2003</td>
                        <td>Dan Knights</td>
                        <td>20.00s</td>
                    </tr>
                    <tr>
                        <td>2018</td>
                        <td>Yusheng Du</td>
                        <td>3.47s</td>
                    </tr>
                    <tr>
                        <td>2024</td>
                        <td>Xuanyi Geng</td>
                        <td>3.05s</td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h3>💡 دانستنی‌های روبیک</h3>
                <p style="font-size: 0.85em; line-height: 1.5; margin:0;">
                    مکعب روبیک در سال 1974 توسط <strong>ارنو روبیک</strong> ساخته شد. این مکعب حدود 43 کوئینتیلیون حالت
                    مختلف دارد اما با الگوریتم‌های استاندارد در کمتر از 20 حرکت قابل حل است!
                </p>
            </div>
        </div>
    </div>

    <script>
    // --- 1. Three.js Rubik's Cube Setup (Advanced Version) ---
    debugger;
    const container = document.getElementById('cube-container');
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({
        antialias: true
    });
    renderer.setSize(container.clientWidth, container.clientHeight);
    container.appendChild(renderer.domElement);

    // ایجاد یک گروه برای نگهداری تمام قطعات مکعب
    const cubeGroup = new THREE.Group();
    scene.add(cubeGroup);

    // رنگ‌های استاندارد روبیک
    const colors = [
        0xff0000, // راست: قرمز
        0xffa500, // چپ: نارنجی
        0xffffff, // بالا: سفید
        0xffff00, // پایین: زرد
        0x00ff00, // جلو: سبز
        0x0000ff // عقب: آبی
    ];

    // ایجاد ۲۷ قطعه کوچک برای ساخت مکعب ۳x۳
    for (let x = -1; x <= 1; x++) {
        for (let y = -1; y <= 1; y++) {
            for (let z = -1; z <= 1; z++) {
                const geometry = new THREE.BoxGeometry(0.95, 0.95, 0.95); // کمی فاصله برای دیدن خطوط
                const materials = colors.map(color => new THREE.MeshBasicMaterial({
                    color: color
                }));
                const piece = new THREE.Mesh(geometry, materials);

                piece.position.set(x, y, z);
                cubeGroup.add(piece);
            }
        }
    }

    camera.position.set(5, 5, 5);
    camera.lookAt(0, 0, 0);

    function animateCube() {
        requestAnimationFrame(animateCube);
        cubeGroup.rotation.x += 0.005;
        cubeGroup.rotation.y += 0.005;
        renderer.render(scene, camera);
    }
    animateCube();


    // --- 2. Timer Logic ---
    let startTime, tInterval;
    let running = false;
    let savedSeconds = 0;

    function startTimer() {
        if (!running) {
            startTime = new Date().getTime() - (savedSeconds || 0);
            tInterval = setInterval(getShowTime, 10);
            running = true;
        }
    }

    function stopTimer() {
        if (running) {
            clearInterval(tInterval);
            running = false;
            savedSeconds = new Date().getTime() - startTime;
            let displayTime = document.getElementById('timerDisplay').innerText;
            document.getElementById('recordTime').value = displayTime;
        }
    }

    function resetTimer() {
        clearInterval(tInterval);
        running = false;
        savedSeconds = 0;
        document.getElementById('timerDisplay').innerHTML = "00:00.00";
        document.getElementById('recordTime').value = "";
    }

    function getShowTime() {
        let updatedTime = new Date().getTime() - startTime;
        savedSeconds = updatedTime;
        let minutes = Math.floor((updatedTime % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((updatedTime % (1000 * 60)) / 1000);
        let milliseconds = Math.floor((updatedTime % 1000) / 10);

        minutes = (minutes < 10) ? "0" + minutes : minutes;
        seconds = (seconds < 10) ? "0" + seconds : seconds;
        milliseconds = (milliseconds < 10) ? "0" + milliseconds : milliseconds;

        document.getElementById('timerDisplay').innerHTML = `${minutes}:${seconds}.${milliseconds}`;
    }

    // --- 3. Chart.js Setup ---


    // --- 4. Database Save Simulation / AJAX ---
    function saveRecordToDB(event) {
        event.preventDefault();
        let name = document.getElementById('userName').value;
        let time = document.getElementById('recordTime').value;

        if (!time) {
            alert("ابتدا باید تایمر را متوقف کنید تا زمانی ثبت شود!");
            return;
        }

        // ارسال داده به فایل PHP (برای XAMPP)
        fetch('rubik.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `name=${encodeURIComponent(name)}&time=${encodeURIComponent(time)}`
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('saveStatus').innerText = "رکورد با موفقیت در دیتابیس ذخیره شد!";
            })
            .catch(error => {
                document.getElementById('saveStatus').innerText = "خطا در ارتباط با دیتابیس!";
            });
    }
    </script>

</body>

</html>