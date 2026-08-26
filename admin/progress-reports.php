<?php
session_start();
error_reporting(0);
include('include/config.php');
if (strlen($_SESSION['adminid'])==0) {
    header('location:logout.php');
    exit;
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | Progress Reports</title>
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="app sidebar-mini rtl">
    <!-- Navbar-->
    <?php include 'include/header.php'; ?>
    <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    <?php include 'include/sidebar.php'; ?>

    <main class="app-content">
        <div class="app-title">
            <div>
                <h1><i class="fa fa-bar-chart"></i> Progress Reports</h1>
                <p>Track member fitness goals, workout frequency, and overall progress</p>
            </div>
            <ul class="app-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item">Progress Reports</li>
            </ul>
        </div>

        <marquee onMouseOver="this.stop()" style="color: #e92f33;" onMouseOut="this.start()">Welcome to HOTSPORT GYM SYSTEM | Manage Members, Packages, Bookings and Payments</marquee>

        <!-- Filter Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-filter"></i> Filter Reports</h3>
                    <div class="tile-body">
                        <form id="filter-form" class="form-row align-items-end">
                            <div class="form-group col-md-3">
                                <label for="from_date"><b>From Date:</b></label>
                                <input type="date" class="form-control" id="from_date" name="from_date">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="to_date"><b>To Date:</b></label>
                                <input type="date" class="form-control" id="to_date" name="to_date">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="member_id"><b>Member:</b></label>
                                <select class="form-control" id="member_id" name="member_id">
                                    <option value="">-- All Members --</option>
                                    <?php
                                    $sql = "SELECT id, fname, lname FROM tbluser ORDER BY fname, lname";
                                    $query = $dbh->prepare($sql);
                                    $query->execute();
                                    $members = $query->fetchAll(PDO::FETCH_OBJ);
                                    foreach($members as $member) {
                                    ?>
                                    <option value="<?php echo htmlentities($member->id); ?>"><?php echo htmlentities($member->fname . ' ' . $member->lname); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <button type="button" id="btn-filter" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                                <button type="button" id="btn-reset" class="btn btn-secondary"><i class="fa fa-refresh"></i> Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics Widgets -->
        <div class="row">
            <div class="col-md-4">
                <div class="widget-small warning coloured-icon">
                    <i class="icon fa fa-hourglass-half fa-3x"></i>
                    <div class="info">
                        <h4>Active Goals</h4>
                        <p><b id="active-goals-count">0</b></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="widget-small success coloured-icon">
                    <i class="icon fa fa-check-circle fa-3x"></i>
                    <div class="info">
                        <h4>Completed Goals</h4>
                        <p><b id="completed-goals-count">0</b></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="widget-small primary coloured-icon">
                    <i class="icon fa fa-fire fa-3x"></i>
                    <div class="info">
                        <h4>Total Workouts</h4>
                        <p><b id="total-workouts-count">0</b></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-pie-chart"></i> Goals Progress Overview</h3>
                    <div class="embed-responsive embed-responsive-16by9">
                        <canvas class="embed-responsive-item" id="goalsProgressChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-bar-chart"></i> Workout Frequency</h3>
                    <div class="embed-responsive embed-responsive-16by9">
                        <canvas class="embed-responsive-item" id="workoutFrequencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Goals Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-bullseye"></i> Member Goals</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
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
                                <tr><td colspan="9" class="text-center text-muted">Loading goals...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Workouts Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-history"></i> Recent Workouts</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Workout Date</th>
                                    <th>Duration</th>
                                    <th>Calories Burned</th>
                                    <th>Exercises</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="workouts-table-body">
                                <tr><td colspan="8" class="text-center text-muted">Loading workouts...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include_once 'include/footer.php'; ?>

    <!-- Essential javascripts for application to work-->
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="js/plugins/pace.min.js"></script>

    <script>
    var goalsChart = null;
    var workoutChart = null;

    $(document).ready(function() {
        setupCharts();
        loadProgressData();

        $('#btn-filter').on('click', function() {
            loadProgressData();
        });

        $('#btn-reset').on('click', function() {
            $('#from_date').val('');
            $('#to_date').val('');
            $('#member_id').val('');
            loadProgressData();
        });

        $('#member_id').on('change', function() {
            loadProgressData();
        });
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
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

    function loadProgressData() {
        var fromDate = $('#from_date').val();
        var toDate = $('#to_date').val();
        var memberId = $('#member_id').val();

        $.ajax({
            url: 'progress-reports-data.php',
            method: 'POST',
            data: {
                action: 'get_summary',
                from_date: fromDate,
                to_date: toDate,
                member_id: memberId
            },
            dataType: 'json',
            success: function(data) {
                $('#active-goals-count').text(data.active_goals || 0);
                $('#completed-goals-count').text(data.completed_goals || 0);
                $('#total-workouts-count').text(data.total_workouts || 0);

                if (goalsChart && data.goals_data) {
                    goalsChart.data.datasets[0].data = [data.goals_data.completed || 0, data.goals_data.in_progress || 0];
                    goalsChart.update();
                }

                if (workoutChart && data.workout_data) {
                    workoutChart.data.labels = data.workout_data.labels || [];
                    workoutChart.data.datasets[0].data = data.workout_data.data || [];
                    workoutChart.update();
                }

                loadGoalsTable(data.goals_list || []);
                loadWorkoutsTable(data.workouts_list || []);
            },
            error: function(xhr, status, error) {
                console.error("Error loading progress reports data:", error);
            }
        });
    }

    function loadGoalsTable(goalsList) {
        var tbody = $('#goals-table-body');
        tbody.empty();

        if (!goalsList || goalsList.length === 0) {
            tbody.append('<tr><td colspan="9" class="text-center text-muted">No goals found for the selected criteria.</td></tr>');
            return;
        }

        goalsList.forEach(function(goal, index) {
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
                '<td>' + (index + 1) + '</td>' +
                '<td><a href="view-member-progress.php?id=' + encodeURIComponent(goal.user_id) + '"><strong>' + $('<div>').text(goal.member_name).html() + '</strong></a></td>' +
                '<td>' + $('<div>').text(goal.goal_type).html() + '</td>' +
                '<td>' + $('<div>').text(goal.target_value + ' ' + (goal.unit || '')).html() + '</td>' +
                '<td>' + $('<div>').text(goal.current_value + ' ' + (goal.unit || '')).html() + '</td>' +
                '<td>' +
                    '<div class="progress" style="height: 15px;">' +
                        '<div class="progress-bar ' + progressBarClass + '" role="progressbar" style="width: ' + progressPercent + '%" aria-valuenow="' + progressPercent + '" aria-valuemin="0" aria-valuemax="100">' + progressPercent + '%</div>' +
                    '</div>' +
                '</td>' +
                '<td>' + $('<div>').text(goal.target_date).html() + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td><a href="view-member-progress.php?id=' + encodeURIComponent(goal.user_id) + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a></td>' +
                '</tr>';
            tbody.append(row);
        });
    }

    function loadWorkoutsTable(workoutsList) {
        var tbody = $('#workouts-table-body');
        tbody.empty();

        if (!workoutsList || workoutsList.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">No workouts found for the selected criteria.</td></tr>');
            return;
        }

        workoutsList.forEach(function(workout, index) {
            var row = '<tr>' +
                '<td>' + (index + 1) + '</td>' +
                '<td><a href="view-member-progress.php?id=' + encodeURIComponent(workout.user_id) + '"><strong>' + $('<div>').text(workout.member_name).html() + '</strong></a></td>' +
                '<td>' + $('<div>').text(workout.workout_date).html() + '</td>' +
                '<td>' + $('<div>').text((workout.duration || 0) + ' min').html() + '</td>' +
                '<td>' + $('<div>').text((workout.calories_burned || 0) + ' kcal').html() + '</td>' +
                '<td><span class="badge badge-info">' + (workout.exercise_count || 0) + ' exercises</span></td>' +
                '<td>' + $('<div>').text(workout.notes || '-').html() + '</td>' +
                '<td><a href="view-member-progress.php?id=' + encodeURIComponent(workout.user_id) + '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a></td>' +
                '</tr>';
            tbody.append(row);
        });
    }
    </script>
</body>
</html>
<?php } ?>