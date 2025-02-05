<?php
require_once 'db.php';

$sql = "SELECT * FROM appointments";
$result = $conn->query($sql);
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
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .card {
        width: 100%;
        max-width: 1100px;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        background: white;
        margin-top: 50px;
        margin: 20px;
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
    </style>
</head>

<body>

    <div class="card">
        <h3 class="text-center mb-4">ข้อมูลการนัดหมาย</h3>

        <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>บริการที่นัดหมาย</th>
                        <th>วันที่นัดหมาย</th>
                        <th>เวลานัดหมาย</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['service']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['appointment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                        <td>
                            <a href="#" class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>"
                                data-service="<?php echo $row['service']; ?>"
                                data-appointment_date="<?php echo date('d/m/Y', strtotime($row['appointment_date'])); ?>"
                                data-appointment_time="<?php echo $row['appointment_time']; ?>">ยกเลิก</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-center">ไม่มีข้อมูลการนัดหมาย</p>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-custom">กลับไปยังหน้าหลัก</a>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">❌ ต้องการยกเลิกการจองคิวใช่ไหม ?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteService"></p>
                    <p>
                        <span id="deleteDate"></span>
                        <span id="deleteTime"></span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDelete">ตกลง</button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
</body>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
    $(".delete-btn").on("click", function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var firstname = $(this).data('firstname');
        var lastname = $(this).data('lastname');
        var service = $(this).data('service');
        var appointment_date = $(this).data('appointment_date');
        var appointment_time = $(this).data('appointment_time');

        $('#deleteFirstLast').text(firstname + ' ' + lastname);
        $('#deleteService').text(service);
        $('#deleteDate').text(appointment_date);
        $('#deleteTime').text(appointment_time);
        $('#deleteModal').modal('show');
        $('#confirmDelete').data('id', id);
    });

    $('#confirmDelete').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'delete_appointment.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                console.log(response);
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
});

</script>

</html>