<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM appointments_service";
$sql_date = "SELECT * FROM appointments_date";
$result = $conn->query($sql);
$result_date = $conn->query($sql_date);

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลการนัดหมาย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    body {
        background: linear-gradient(135deg, #d4f8e8, #b8e0d2);
        font-family: 'Arial', sans-serif;
        height: 100vh;
        margin: 0;
    }

    .card {
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        background: white;
        margin-top: 50px;
        margin: 5% 20%;
    }

    .table th,
    .table td {
        text-align: center;
    }

    .table {
        background: #f8f9fa;
        border-radius: 10px;
    }

    .table th {
        background-color: #5fc4b8;
        color: white;
    }

    .btn-custom {
        background-color: #5fc4b8;
        color: white;
        font-weight: bold;
        border-radius: 8px;
    }

    .btn-custom:hover {
        background-color: #48a69d;
    }

    .modal-dialog {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;

    }

    .modal-content {
        width: 100%;
        max-width: 500px;
    }

    .display-flex {
        display: flex;
        justify-content: center;
    }

    .display-flex .btn-primary {
        margin-right: 10px;
        margin-left: 10px;
    }

    .form-control {
        width: min-content;
    }

    .btn-confirm-date {
        display: flex;
        align-items: center;
        margin-top: 16px;
    }

    .tap {
        padding: 2%;
        background-color: #5fc4b8;
        text-align: right;
        font-weight: 600;
    }
    </style>
</head>

<body>
    <div class="tap">
        <a class="nav-link" href="management.php"><i class="fa-solid fa-user"></i>ข้อมูลการนัดหมาย</a>
    </div>

    <div class="card">
        <h3 class="text-center mb-4">เพิ่มบริการที่นัดหมาย</h3>

        <div class="table-responsive">
            <form id="add-appointmentForm">
                <div class="mb-3 display-flex">
                    <input type="text" name="service_name" class="form-control" required>
                    <button type="submit" class="btn btn-primary w-48 mr-3">ตกลง</button>
                    <button type="reset" class="btn btn-danger w-48">ยกเลิก</button>
                </div>
            </form>

            <div id="responseMessage" class="text-center mt-3"></div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>บริการที่นัดหมาย</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                        <td>
                            <a href="#" class="btn btn-danger btn-sm delete-btn"
                                data-id="<?php echo $row['service_id']; ?>"
                                data-appointment_time="<?php echo $row['service_name']; ?>">ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="2" class="text-center">ไม่มีข้อมูล</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal สำหรับลบบริการ -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">❌ ต้องการลบบริการใช่ไหม ?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteService"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDeleteService">ตกลง</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <h3 class="text-center mb-4">ปิดวันที่ทำการ</h3>
        <div class="table-responsive">
            <form id="add-appointmentDateForm">
                <div class="mb-3 display-flex">
                    <div class="mb-3">
                        <label class="form-label">📅 วันที่ปิดทำการ</label>
                        <input type="text" id="datepicker" name="appointments_date" class="form-control" required>
                    </div>
                    <div class="btn-confirm-date">
                        <button type="submit" class="btn btn-primary w-48 mr-3">ตกลง</button>
                        <button type="reset" class="btn btn-danger w-48">ยกเลิก</button>
                    </div>
                </div>
            </form>
            <div id="responseMessage" class="text-center mt-3"></div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_date->num_rows > 0): ?>
                    <?php while($row_date = $result_date->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row_date['appointments_date']); ?></td>
                        <td>
                            <a href="#" class="btn btn-danger btn-sm delete-btn-date"
                                data-id="<?php echo $row_date['appointments_date_id']; ?>"
                                data-appointment_time="<?php echo $row_date['appointments_date']; ?>">ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="2" class="text-center">ไม่มีข้อมูล</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="deleteDateModal" tabindex="-1" aria-labelledby="deleteDateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDateModalLabel">❌ ต้องการลบวันที่ใช่ไหม ?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteDate"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDeleteDate">ตกลง</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
</body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>


<script>
$(document).ready(function() {
    $("#datepicker").datepicker({
        dateFormat: "yy-mm-dd"
    });
});
$(document).ready(function() {
    $(".delete-btn").on("click", function(e) {
        e.preventDefault();

        var id = $(this).data('id');
        var service = $(this).data('appointment_time');
        $('#deleteService').text(service);
        $('#deleteModal').modal('show');
        $('#confirmDeleteService').data('id', id);
    });

    $('#confirmDeleteService').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'delete_service.php',
            type: 'POST',
            data: {
                id: id
            },
            success: function(response) {
                $('#deleteModal').modal('hide');
                if (response == 'success') {
                    location.reload();
                } else {
                    alert('ไม่สามารถลบข้อมูลได้: ' + response);
                }
            },
            error: function() {
                alert('เกิดข้อผิดพลาดในการลบข้อมูล');
            }
        });
    });

    $(".delete-btn-date").on("click", function(e) {
        e.preventDefault();

        var id = $(this).data('id');
        var date = $(this).data('appointment_time');
        $('#deleteDate').text(date);
        $('#deleteDateModal').modal('show');
        $('#confirmDeleteDate').data('id', id);
    });

    $('#confirmDeleteDate').on('click', function() {
        var id = $(this).data('id');

        $.ajax({
            url: 'delete_date.php',
            type: 'POST',
            data: {
                id: id
            },
            success: function(response) {
                $('#deleteDateModal').modal('hide');
                if (response == 'success') {
                    location.reload();
                } else {
                    alert('ไม่สามารถลบข้อมูลได้: ' + response);
                }
            },
            error: function() {
                alert('เกิดข้อผิดพลาดในการลบข้อมูล');
            }
        });
    });
});

document.getElementById('add-appointmentForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const serviceName = document.querySelector('input[name="service_name"]').value;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_service.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    const data = 'service_name=' + encodeURIComponent(serviceName);
    xhr.onload = function() {
        if (xhr.status === 200) {
            // document.getElementById('responseMessage').innerText = xhr.responseText;
            document.querySelector('input[name="service_name"]').value = '';
            location.reload();
        } else {
            document.getElementById('responseMessage').innerText = 'เกิดข้อผิดพลาดในการส่งข้อมูล';
        }
    };
    xhr.send(data);
});
const form = document.getElementById('add-appointmentForm');
const resetButton = form.querySelector('button[type="reset"]');
resetButton.addEventListener('click', function() {
    form.reset();
});

document.getElementById('add-appointmentDateForm').addEventListener('submit', function(event) {
    event.preventDefault();

    const appointmentsDate = document.querySelector('input[name="appointments_date"]').value;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_date.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    const data = 'appointments_date=' + encodeURIComponent(appointmentsDate);

    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('responseMessage').innerText = xhr.responseText;
            document.querySelector('input[name="appointments_date"]').value = '';
            location.reload();
        } else {
            document.getElementById('responseMessage').innerText = 'เกิดข้อผิดพลาดในการส่งข้อมูล';
        }
    };

    xhr.send(data);
});
</script>

</html>