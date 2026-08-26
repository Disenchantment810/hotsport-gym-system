<?php
session_start();
error_reporting(0);
require_once('include/config.php');
if(strlen($_SESSION["uid"])==0) {
    header('location:login.php');
    exit;
} else {
    $user_id = $_SESSION["uid"];
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <title>Gym Management System | Goal Setting</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/bootstrap.min.css"/>
    <link rel="stylesheet" href="css/font-awesome.min.css"/>
    <link rel="stylesheet" href="css/owl.carousel.min.css"/>
    <link rel="stylesheet" href="css/nice-select.css"/>
    <link rel="stylesheet" href="css/slicknav.min.css"/>
    <link rel="stylesheet" href="css/style.css"/>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .goal-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            padding: 25px;
            margin-bottom: 30px;
        }
        .goal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #f2f2f2;
            padding-bottom: 10px;
        }
        .btn-theme {
            background: #f65d5d;
            color: #fff;
            border: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 4px;
            transition: 0.3s;
        }
        .btn-theme:hover {
            background: #e04444;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <?php include 'include/header.php';?>

    <!-- Page top Section -->
    <section class="page-top-section set-bg" data-setbg="img/page-top-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 m-auto text-white">
                    <h2>Fitness Goals</h2>
                    <p>Set targets, log progress, and achieve your fitness milestones</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="pricing-section spad">
        <div class="container">
            <!-- Set Goal Form -->
            <div class="goal-card">
                <div class="goal-header"><i class="fa fa-plus-circle text-danger"></i> Set a New Fitness Goal</div>
                <form id="goal-form">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="goal_type"><b>Goal Type:</b></label>
                            <select class="form-control" id="goal_type" name="goal_type" required>
                                <option value="">-- Select Goal Type --</option>
                                <option value="weight">Weight (kg)</option>
                                <option value="chest">Chest (cm)</option>
                                <option value="waist">Waist (cm)</option>
                                <option value="hips">Hips (cm)</option>
                                <option value="thighs">Thighs (cm)</option>
                                <option value="biceps">Biceps (cm)</option>
                                <option value="workout_frequency">Workout Frequency (times/wk)</option>
                                <option value="other">Other Goal</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="target_value"><b>Target Value:</b></label>
                            <input type="number" step="0.01" class="form-control" id="target_value" name="target_value" placeholder="e.g. 75" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="unit"><b>Unit:</b></label>
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="e.g. kg" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label for="target_date"><b>Target Date:</b></label>
                            <input type="date" class="form-control" id="target_date" name="target_date" required>
                        </div>
                        <div class="col-md-2 form-group align-self-end">
                            <button type="submit" class="btn btn-theme btn-block"><i class="fa fa-check"></i> Set Goal</button>
                        </div>
                    </div>
                </form>
                <div id="form-message" class="mt-2"></div>
            </div>

            <!-- Goals Overview and Chart Row -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="goal-card">
                        <div class="goal-header"><i class="fa fa-list-ul text-primary"></i> My Active & Completed Goals</div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Goal Type</th>
                                        <th>Target</th>
                                        <th>Current</th>
                                        <th>Progress</th>
                                        <th>Target Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="goals-table-body">
                                    <tr><td colspan="7" class="text-center text-muted">Loading your goals...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="goal-card text-center">
                        <div class="goal-header"><i class="fa fa-pie-chart text-success"></i> Completion Ratio</div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="goalsProgressChart"></canvas>
                        </div>
                        <div class="mt-3 text-muted">
                            <small>Keep pushing! Consistent logging builds consistent results.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <?php include 'include/footer.php'; ?>

    <div class="back-to-top"><img src="img/icons/up-arrow.png" alt=""></div>

    <!-- Javascripts -->
    <script src="js/vendor/jquery-3.2.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.slicknav.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/jquery.nice-select.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    var userId = <?php echo $user_id; ?>;
    var goalsChart = null;

    $(document).ready(function() {
        setupGoalsChart();
        loadGoalsData();

        $('#goal_type').change(function() {
            var selectedType = $(this).val();
            var unitMap = {
                'weight': 'kg',
                'chest': 'cm',
                'waist': 'cm',
                'hips': 'cm',
                'thighs': 'cm',
                'biceps': 'cm',
                'workout_frequency': 'times/wk'
            };
            if(selectedType === 'other') {
                $('#unit').val('').prop('readonly', false).focus();
            } else {
                $('#unit').val(unitMap[selectedType] || '').prop('readonly', true);
            }
        });

        $('#goal-form').submit(function(e) {
            e.preventDefault();
            submitGoalForm();
        });
    });

    function setupGoalsChart() {
        var ctx = document.getElementById('goalsProgressChart').getContext('2d');
        goalsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['#28a745', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                }
            }
        });
    }

    function loadGoalsData() {
        $.ajax({
            url: 'goal-setting-data.php',
            method: 'POST',
            data: {
                action: 'get_goals',
                user_id: userId
            },
            dataType: 'json',
            success: function(data) {
                loadGoalsTable(data.goals_list || []);
                if(goalsChart && data.goals_chart_data) {
                    goalsChart.data.datasets[0].data = [
                        data.goals_chart_data.completed || 0,
                        data.goals_chart_data.in_progress || 0
                    ];
                    goalsChart.update();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading goals data:", error);
            }
        });
    }

    function submitGoalForm() {
        var goal_type = $('#goal_type').val();
        var target_value = $('#target_value').val();
        var unit = $('#unit').val();
        var target_date = $('#target_date').val();

        if(!goal_type || !target_value || !unit || !target_date) {
            showFormMessage('Please fill all fields', 'warning');
            return;
        }

        $.ajax({
            url: 'goal-setting-data.php',
            method: 'POST',
            data: {
                action: 'add_goal',
                goal_type: goal_type,
                target_value: target_value,
                unit: unit,
                target_date: target_date
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showFormMessage('Goal added successfully!', 'success');
                    $('#goal-form')[0].reset();
                    $('#unit').prop('readonly', false);
                    loadGoalsData();
                } else {
                    showFormMessage(response.message || 'Error creating goal', 'danger');
                }
            },
            error: function() {
                showFormMessage('Server error occurred', 'danger');
            }
        });
    }

    function loadGoalsTable(goalsList) {
        var tbody = $('#goals-table-body');
        tbody.empty();

        if(!goalsList || goalsList.length === 0) {
            tbody.append('<tr><td colspan="7" class="text-center text-muted">You have not set any fitness goals yet.</td></tr>');
            return;
        }

        goalsList.forEach(function(goal) {
            var targetVal = parseFloat(goal.target_value) || 0;
            var currentVal = parseFloat(goal.current_value) || 0;
            var progressPercent = targetVal > 0 ? (currentVal / targetVal) * 100 : 0;
            if(progressPercent > 100) progressPercent = 100;
            progressPercent = Math.round(progressPercent);

            var isCompleted = currentVal >= targetVal && targetVal > 0;
            var statusBadge = isCompleted 
                ? '<span class="badge badge-success">Completed</span>' 
                : '<span class="badge badge-warning">In Progress</span>';
            var progressBarClass = isCompleted ? 'bg-success' : 'bg-warning';

            var row = '<tr>' +
                '<td><strong>' + $('<div>').text(goal.goal_type).html() + '</strong></td>' +
                '<td>' + $('<div>').text(goal.target_value + ' ' + (goal.unit || '')).html() + '</td>' +
                '<td>' +
                    '<div class="input-group input-group-sm" style="max-width: 140px;">' +
                        '<input type="number" step="0.01" class="form-control form-control-sm current-val-input" data-id="' + goal.id + '" value="' + currentVal + '">' +
                        '<div class="input-group-append">' +
                            '<button class="btn btn-outline-secondary btn-sm update-progress-btn" data-id="' + goal.id + '" title="Update Progress"><i class="fa fa-save"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<div class="progress" style="height: 14px; min-width: 80px;">' +
                        '<div class="progress-bar ' + progressBarClass + '" role="progressbar" style="width: ' + progressPercent + '%" aria-valuenow="' + progressPercent + '" aria-valuemin="0" aria-valuemax="100">' + progressPercent + '%</div>' +
                    '</div>' +
                '</td>' +
                '<td>' + $('<div>').text(goal.target_date).html() + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td>' +
                    '<button class="btn btn-sm btn-outline-danger delete-goal-btn" data-id="' + goal.id + '" title="Delete Goal"><i class="fa fa-trash"></i></button>' +
                '</td>' +
                '</tr>';
            tbody.append(row);
        });

        // Bind update progress handler
        $('.update-progress-btn').on('click', function() {
            var goalId = $(this).data('id');
            var currentVal = $(this).closest('tr').find('.current-val-input').val();
            updateGoalProgress(goalId, currentVal);
        });

        // Bind delete handler
        $('.delete-goal-btn').on('click', function() {
            var goalId = $(this).data('id');
            if(confirm('Are you sure you want to delete this goal?')) {
                deleteGoal(goalId);
            }
        });
    }

    function updateGoalProgress(goalId, currentVal) {
        $.ajax({
            url: 'goal-setting-data.php',
            method: 'POST',
            data: {
                action: 'update_goal_progress',
                goal_id: goalId,
                current_value: currentVal
            },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    showFormMessage('Goal progress updated successfully!', 'success');
                    loadGoalsData();
                } else {
                    showFormMessage(res.message || 'Failed to update progress', 'danger');
                }
            }
        });
    }

    function deleteGoal(goalId) {
        $.ajax({
            url: 'goal-setting-data.php',
            method: 'POST',
            data: {
                action: 'delete_goal',
                goal_id: goalId
            },
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    showFormMessage('Goal deleted successfully!', 'success');
                    loadGoalsData();
                } else {
                    showFormMessage(res.message || 'Failed to delete goal', 'danger');
                }
            }
        });
    }

    function showFormMessage(message, type) {
        $('#form-message').html('<div class="alert alert-' + type + ' alert-dismissible fade show mt-2" role="alert">' +
            message + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
        setTimeout(function() {
            $('#form-message .alert').alert('close');
        }, 5000);
    }
    </script>
</body>
</html>
<?php } ?>