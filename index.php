<?php
require_once('includes/config.php');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT appointments_date FROM appointments_date";
$result = $conn->query($sql);

$bookedDates = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookedDates[] = $row['appointments_date'];
    }
}

echo "<script>var bookedDates = " . json_encode($bookedDates) . ";</script>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $service = $_POST['service'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';

    $appointment_date = isset($_POST['appointment_date']) ? DateTime::createFromFormat('d/m/Y', $_POST['appointment_date']) : false;

    if ($appointment_date) {
        $appointment_date = $appointment_date->format('Y-m-d');
    } else {
        die("❌ รูปแบบวันที่ไม่ถูกต้อง");
    }

    $sql = "INSERT INTO appointments (firstname, lastname, phone, service, appointment_date, appointment_time) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $firstname, $lastname, $phone, $service, $appointment_date, $appointment_time);

    if ($stmt->execute()) {
        echo "
        <script>
            window.onload = function() {
                var myModal = new bootstrap.Modal(document.getElementById('successModal'));
                myModal.show();
            }
        </script>";
    } else {
        echo "❌ เกิดข้อผิดพลาด: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบฟอร์มนัดหมาย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <style>
    body {
        background: linear-gradient(135deg, #74ebd5, #acb6e5);
        font-family: 'Arial', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;

    }

    .btn-danger {
        background: #ff6b6b;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
        font-weight: bold;
    }

    .btn-danger:hover {
        background: #e55e5e;
    }

    .form-label::after {
        content: " *";
        color: red;
        font-weight: bold;
    }

    .card {
        max-width: 500px;
        width: 100%;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        background: white;
        margin: 30px;
        position: relative;
    }

    .card::before {
        content: "🦷";
        font-size: 5rem;
        position: absolute;
        top: -40px;
        left: 50%;
        transform: translateX(-50%);
        opacity: 0.2;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        box-shadow: none;
        border: 1px solid #ddd;
        transition: 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #74ebd5;
        box-shadow: 0px 0px 5px rgba(116, 235, 213, 0.5);
    }

    .btn-primary {
        background: #74ebd5;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
        font-weight: bold;
        width: 40%;
    }

    .btn-primary:hover {
        background: #5fc4b8;
    }

    .btn-danger {
        background: #ff6b6b;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
        font-weight: bold;
        width: 40%;
    }

    .btn-danger:hover {
        background: #e55e5e;
    }

    .form-label::after {
        content: " *";
        color: red;
        font-weight: bold;
    }

    .modal-dialog {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;

    }

    #errorModal {
        padding: 30px;
    }
    </style>
</head>

<body>
    <div class="card">
        <h3 class="text-center mb-4">📝 นัดหมายบริการ</h3>
        <form id="appointmentForm">
            <div class="mb-3">
                <label class="form-label">ชื่อ</label>
                <input type="text" name="firstname" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">นามสกุล</label>
                <input type="text" name="lastname" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">หมายเลขโทรศัพท์</label>
                <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}" required
                    placeholder="กรอกหมายเลข 10 หลัก" title="กรุณากรอกหมายเลขโทรศัพท์ 10 หลัก"
                    oninput="validatePhone(this)">
            </div>
            <div class="mb-3">
                <label class="form-label">บริการที่นัดหมาย</label>
                <div>
                    <select class="form-select" name="service">
                        <option value="" disabled selected>เลือก</option>

                        <?php
            $sql = "SELECT service_id, service_name FROM appointments_service";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $service_id = $row['service_id'];
                    $service_name = $row['service_name'];
                    echo '<option value="' . htmlspecialchars($service_id) . '">' . htmlspecialchars($service_name) . '</option>';
                }
            } else {
                echo '<option value="">ไม่มีข้อมูลบริการ</option>';
            }
            ?>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">📅 วันที่ทำการนัดหมาย</label>
                <input type="text" id="datepicker" name="appointment_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">🕒 เวลานัดหมาย</label>
                <select name="appointment_time" class="form-select" required>
                    <option value="">เลือกช่วงเวลา</option>
                    <option value="09:00 - 10:00 น.">09:00 - 10:00 น.</option>
                    <option value="10:30 - 11:30 น.">10:30 - 11:30 น.</option>
                    <option value="13:00 - 14:00 น.">13:00 - 14:00 น.</option>
                    <option value="14:00 - 15:00 น.">14:00 - 15:00 น.</option>
                </select>
            </div>

            <div class="d-flex justify-content-between" style="margin: 0 40px;">
                <button type="submit" class="btn btn-primary w-48">ตกลง</button>
                <button type="reset" class="btn btn-danger w-48">ยกเลิก</button>
            </div>
        </form>
    </div>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">✅ บันทึกข้อมูลเรียบร้อย!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ข้อมูลถูกบันทึกเรียบร้อยแล้ว
                </div>
                <div class="modal-footer">
                    <a href="index.php" class="btn btn-primary">ปิด</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">❌ ข้อผิดพลาด</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    กรุณาใส่ข้อมูลใหม่
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

    <script>
    $(document).ready(function() {
        function showModal(modalId, message) {
            $(`#${modalId} .modal-body`).text(message);
            $(`#${modalId}`).modal('show');
            $(`#${modalId}`).removeAttr("inert");
        }
        $("#appointmentForm").on("submit", function(e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                url: "submit.php",
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.error) {
                        showModal('errorModal', response.error);
                    } else if (response.success) {
                        showModal('successModal', "จองคิวเรียบร้อยแล้ว");
                    }
                },
                error: function() {
                    showModal('errorModal', "❌ เกิดข้อผิดพลาดในการส่งข้อมูล");
                }
            });
        });

        $('#successModal, #errorModal').on('hidden.bs.modal', function() {
            $(this).attr('inert', 'true');
        });

        $("#datepicker").datepicker({
            dateFormat: "dd/mm/yy",
            minDate: 0,
            beforeShowDay: function(date) {
                var string = $.datepicker.formatDate('yy-mm-dd', date);
                return [bookedDates.indexOf(string) == -1];
            }
        });

        function checkAvailability(dateText) {
            $.ajax({
                url: "check_availability.php",
                type: "POST",
                data: {
                    appointment_date: dateText
                },
                dataType: "json",
                success: function(bookedTimes) {
                    $("select[name='appointment_time'] option").each(function() {
                        const isBooked = bookedTimes.includes($(this).val());
                        $(this).prop("disabled", isBooked);
                    });
                }
            });
        }
    });

    function validatePhone(input) {
        const value = input.value;
        const phonePattern = /^[0-9]{10}$/;
        if (!phonePattern.test(value)) {
            input.setCustomValidity("กรุณากรอกหมายเลขโทรศัพท์ที่ถูกต้อง (10 หลัก)");
        } else {
            input.setCustomValidity("");
        }
    }
    </script>
</body>

</html>