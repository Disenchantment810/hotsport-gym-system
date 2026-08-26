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
    <title>Gym Management System | Progress Dashboard</title>
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
        .dash-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            padding: 25px;
            margin-bottom: 30px;
        }
        .dash-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #f2f2f2;
            padding-bottom: 10px;
        }
        .stat-box {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            border-left: 4px solid #f65d5d;
        }
        .stat-box.success { border-left-color: #28a745; }
        .stat-box.warning { border-left-color: #ffc107; }
        .stat-box.info { border-left-color: #17a2b8; }
        .stat-box.primary { border-left-color: #6063eb; }
        .stat-icon {
            font-size: 32px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f8f9fa;
            margin-right: 15px;
        }
        .stat-info h3 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 2px;
            color: #222;
        }
        .stat-info p {
            margin-bottom: 0;
            color: #777;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-theme {
            background: #f65d5d;
            color: #fff;
            border: none;
            font-weight: 600;
            padding: 8px 16px;
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
                    <h2>Fitness Dashboard</h2>
                    <p>Overview of your personal fitness journey, goals, and training routines</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="pricing-section spad">
        <div class="container">
            <!-- Quick Action Links -->
            <div class="row mb-3">
                <div class="col-12 d-flex justify-content-end">
                    <a href="goal-setting.php" class="btn btn-theme mr-2"><i class="fa fa-bullseye"></i> Set New Goal</a>
                    <a href="workout-logging.php" class="btn btn-outline-dark"><i class="fa fa-plus"></i> Log Workout</a>
                </div>
            </div>

            <!-- Overview Stat Widgets Row -->
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box warning">
                        <div class="stat-icon text-warning"><i class="fa fa-hourglass-half"></i></div>
                        <div class="stat-info">
                            <h3 id="active-goals-count">0</h3>
                            <p>Active Goals</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box success">
                        <div class="stat-icon text-success"><i class="fa fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3 id="completed-goals-count">0</h3>
                            <p>Completed Goals</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box primary">
                        <div class="stat-icon text-primary"><i class="fa fa-fire"></i></div>
                        <div class="stat-info">
                            <h3 id="total-workouts-count">0</h3>
                            <p>Total Workouts</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-box info">
                        <div class="stat-icon text-info"><i class="fa fa-clock-o"></i></div>
                        <div class="stat-info">
                            <h3 id="avg-duration-count">0 <small style="font-size: 14px;">min</small></h3>
                            <p>Avg Session Duration</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mt-2">
                <div class="col-lg-5">
                    <div class="dash-card">
                        <div class="dash-header"><i class="fa fa-pie-chart text-success"></i> Goals Completion</div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="goalsProgressChart"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <a href="goal-setting.php" class="btn btn-sm btn-outline-secondary">Manage Goals <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="dash-card">
                        <div class="dash-header"><i class="fa fa-bar-chart text-primary"></i> Workout Activity (Last 7 Days)</div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="workoutFrequencyChart"></canvas>
                        </div>
                        <div class="text-center mt-3">
                            <a href="workout-logging.php" class="btn btn-sm btn-outline-secondary">View All Workouts <i class="fa fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tables Row -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="dash-card">
                        <div class="dash-header d-flex justify-content-between align-items-center">
                            <span><i class="fa fa-bullseye text-danger"></i> Recent Goals</span>
                            <a href="goal-setting.php" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i> New</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Goal</th>
                                        <th>Target</th>
                                        <th>Current</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-goals-table-body">
                                    <tr><td colspan="5" class="text-center text-muted">Loading goals...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="dash-card">
                        <div class="dash-header d-flex justify-content-between align-items-center">
                            <span><i class="fa fa-history text-primary"></i> Recent Workouts</span>
                            <a href="workout-logging.php" class="btn btn-sm btn-outline-primary"><i class="fa fa-plus"></i> Log</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Duration</th>
                                        <th>Calories</th>
                                        <th>Exercises</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-workouts-table-body">
                                    <tr><td colspan="4" class="text-center text-muted">Loading workouts...</td></tr>
                                </tbody>
                            </table>
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
    var workoutChart = null;

    $(document).ready(function() {
        setupCharts();
        loadDashboardData();
    });

    function setupCharts() {
        var goalsCtx = document.getElementById('goalsProgressChart').getContext('2d');
        goalsChart = new Chart(goalsCtx, {
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

        var workoutCtx = document.getElementById('workoutFrequencyChart').getContext('2d');
        workoutChart = new Chart(workoutCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Workouts',
                    data: [],
                    backgroundColor: '#6063eb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1
                        }
                    }]
                }
            }
        });
    }

    function loadDashboardData() {
        $.ajax({
            url: 'progress-dashboard-data.php',
            method: 'POST',
            data: {
                action: 'get_dashboard_data',
                user_id: userId
            },
            dataType: 'json',
            success: function(data) {
                if (data.overview) {
                    $('#active-goals-count').text(data.overview.active_goals || 0);
                    $('#completed-goals-count').text(data.overview.completed_goals || 0);
                    $('#total-workouts-count').text(data.overview.total_workouts || 0);
                    $('#avg-duration-count').html((data.overview.avg_duration || 0) + ' <small style="font-size: 14px;">min</small>');
                }

                if (goalsChart && data.goals_chart_data) {
                    goalsChart.data.datasets[0].data = [
                        data.goals_chart_data.completed || 0,
                        data.goals_chart_data.in_progress || 0
                    ];
                    goalsChart.update();
                }

                if (workoutChart && data.workout_chart_data) {
                    workoutChart.data.labels = data.workout_chart_data.labels || [];
                    workoutChart.data.datasets[0].data = data.workout_chart_data.data || [];
                    workoutChart.update();
                }

                loadRecentGoals(data.recent_goals || []);
                loadRecentWorkouts(data.recent_workouts || []);
            },
            error: function(xhr, status, error) {
                console.error("Error loading dashboard data:", error);
            }
        });
    }

    function loadRecentGoals(goals) {
        var tbody = $('#recent-goals-table-body');
        tbody.empty();

        if (!goals || goals.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">No goals set yet. <a href="goal-setting.php">Set a goal</a></td></tr>');
            return;
        }

        goals.forEach(function(goal) {
            var targetVal = parseFloat(goal.target_value) || 0;
            var currentVal = parseFloat(goal.current_value) || 0;
            var progressPercent = targetVal > 0 ? (currentVal / targetVal) * 100 : 0;
            if (progressPercent > 100) progressPercent = 100;
            progressPercent = Math.round(progressPercent);

            var isCompleted = currentVal >= targetVal && targetVal > 0;
            var statusBadge = isCompleted 
                ? '<span class="badge badge-success">Completed</span>' 
                : '<span class="badge badge-warning">In Progress</span>';
            var progressBarClass = isCompleted ? 'bg-success' : 'bg-warning';

            var row = '<tr>' +
                '<td><strong>' + $('<div>').text(goal.goal_type).html() + '</strong></td>' +
                '<td>' + $('<div>').text(goal.target_value + ' ' + (goal.unit || '')).html() + '</td>' +
                '<td>' + $('<div>').text(goal.current_value + ' ' + (goal.unit || '')).html() + '</td>' +
                '<td>' +
                    '<div class="progress" style="height: 12px; min-width: 60px;">' +
                        '<div class="progress-bar ' + progressBarClass + '" role="progressbar" style="width: ' + progressPercent + '%">' + progressPercent + '%</div>' +
                    '</div>' +
                '</td>' +
                '<td>' + statusBadge + '</td>' +
                '</tr>';
            tbody.append(row);
        });
    }

    function loadRecentWorkouts(workouts) {
        var tbody = $('#recent-workouts-table-body');
        tbody.empty();

        if (!workouts || workouts.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center text-muted">No workouts logged yet. <a href="workout-logging.php">Log workout</a></td></tr>');
            return;
        }

        workouts.forEach(function(workout) {
            var row = '<tr>' +
                '<td><strong>' + $('<div>').text(workout.workout_date).html() + '</strong></td>' +
                '<td>' + $('<div>').text((workout.duration || 0) + ' min').html() + '</td>' +
                '<td>' + $('<div>').text((workout.calories_burned || 0) + ' kcal').html() + '</td>' +
                '<td><span class="badge badge-info">' + (workout.exercise_count || 0) + ' exercises</span></td>' +
                '</tr>';
            tbody.append(row);
        });
    }
    </script>
</body>
</html>
<?php } ?>