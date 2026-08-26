<?php
session_start();
error_reporting(0);
include('include/config.php');
if (strlen($_SESSION['adminid'])==0) {
    header('location:logout.php');
    exit;
} else {

// Get all members for selection dropdown
$sql_all = "SELECT id, fname, lname, email, mobile, city, create_date FROM tbluser ORDER BY fname, lname";
$query_all = $dbh->prepare($sql_all);
$query_all->execute();
$all_members = $query_all->fetchAll(PDO::FETCH_ASSOC);

// Get member ID from URL
$member_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
$member_info = null;

if($member_id > 0) {
    $sql = "SELECT * FROM tbluser WHERE id = ?";
    $query = $dbh->prepare($sql);
    $query->bindValue(1, $member_id);
    $query->execute();
    $member_info = $query->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | Member Progress <?php if($member_info) echo '- ' . htmlentities($member_info['fname'] . ' ' . $member_info['lname']); ?></title>
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
                <h1><i class="fa fa-user"></i> Member Progress Details</h1>
                <p><?php echo $member_info ? 'Viewing fitness progress for ' . htmlentities($member_info['fname'] . ' ' . $member_info['lname']) : 'Select a member to view their individual progress reports'; ?></p>
            </div>
            <ul class="app-breadcrumb breadcrumb">
                <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="progress-reports.php">Progress Reports</a></li>
                <li class="breadcrumb-item">Member Progress</li>
            </ul>
        </div>

        <marquee onMouseOver="this.stop()" style="color: #e92f33;" onMouseOut="this.start()">Welcome to HOTSPORT GYM SYSTEM | Manage Members, Packages, Bookings and Payments</marquee>

        <!-- Member Selector Bar -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <div class="tile-body">
                        <form method="GET" action="view-member-progress.php" class="form-row align-items-center">
                            <div class="col-md-6 form-group mb-0">
                                <label for="member_select" class="mr-2"><b>Select Member:</b></label>
                                <select class="form-control" id="member_select" name="id" onchange="this.form.submit()">
                                    <option value="">-- Choose a Member to View Progress --</option>
                                    <?php foreach($all_members as $m): ?>
                                    <option value="<?php echo $m['id']; ?>" <?php if($member_id == $m['id']) echo 'selected'; ?>>
                                        <?php echo htmlentities($m['fname'] . ' ' . $m['lname'] . ' (' . ($m['email'] ? $m['email'] : 'No Email') . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 text-right mb-0">
                                <a href="progress-reports.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Back to Overview Reports</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if($member_info): ?>
        <!-- Member Profile Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="tile-title mb-1"><i class="fa fa-id-card-o text-primary"></i> <?php echo htmlentities($member_info['fname'] . ' ' . $member_info['lname']); ?></h3>
                            <p class="text-muted mb-0">
                                <strong>Email:</strong> <?php echo htmlentities($member_info['email'] ? $member_info['email'] : 'N/A'); ?> | 
                                <strong>Mobile:</strong> <?php echo htmlentities($member_info['mobile'] ? $member_info['mobile'] : 'N/A'); ?> | 
                                <strong>City:</strong> <?php echo htmlentities($member_info['city'] ? $member_info['city'] : 'N/A'); ?> | 
                                <strong>Registered:</strong> <?php echo htmlentities($member_info['create_date'] ? date('M d, Y', strtotime($member_info['create_date'])) : 'N/A'); ?>
                            </p>
                        </div>
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
                    <h3 class="tile-title"><i class="fa fa-pie-chart"></i> Goals Progress</h3>
                    <div class="embed-responsive embed-responsive-16by9">
                        <canvas class="embed-responsive-item" id="goalsProgressChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <label><b>Overall Goal Completion:</b></label>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="overall-progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-bar-chart"></i> Workout Frequency</h3>
                    <div class="embed-responsive embed-responsive-16by9">
                        <canvas class="embed-responsive-item" id="workoutFrequencyChart"></canvas>
                    </div>
                    <div class="row mt-3 text-center">
                        <div class="col-6 border-right">
                            <span class="text-muted d-block">Total Duration</span>
                            <h5 id="total-duration" class="mb-0 text-primary">0 min</h5>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Calories Burned</span>
                            <h5 id="total-calories" class="mb-0 text-danger">0 kcal</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Goals Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-bullseye"></i> Member Goals History</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Goal Type</th>
                                    <th>Target</th>
                                    <th>Current</th>
                                    <th>Progress</th>
                                    <th>Target Date</th>
                                    <th>Status</th>
                                    <th>Days Remaining</th>
                                </tr>
                            </thead>
                            <tbody id="goals-table-body">
                                <tr><td colspan="7" class="text-center text-muted">Loading goals...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workouts History Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-history"></i> Member Workout Logs</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Calories Burned</th>
                                    <th>Exercises</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody id="workouts-table-body">
                                <tr><td colspan="5" class="text-center text-muted">Loading workouts...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Member Directory View when no member is selected -->
        <div class="row">
            <div class="col-md-12">
                <div class="tile">
                    <h3 class="tile-title"><i class="fa fa-users"></i> Member Directory</h3>
                    <p class="text-muted">Select a member below to view their fitness goals, measurements, and workout activity logs.</p>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Member Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>City</th>
                                    <th>Registered Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($all_members)): ?>
                                    <?php foreach($all_members as $index => $member): ?>
                                    <tr>
                                        <td><?php echo ($index + 1); ?></td>
                                        <td><strong><?php echo htmlentities($member['fname'] . ' ' . $member['lname']); ?></strong></td>
                                        <td><?php echo htmlentities($member['email'] ? $member['email'] : 'N/A'); ?></td>
                                        <td><?php echo htmlentities($member['mobile'] ? $member['mobile'] : 'N/A'); ?></td>
                                        <td><?php echo htmlentities($member['city'] ? $member['city'] : 'N/A'); ?></td>
                                        <td><?php echo htmlentities($member['create_date'] ? date('M d, Y', strtotime($member['create_date'])) : 'N/A'); ?></td>
                                        <td>
                                            <a href="view-member-progress.php?id=<?php echo htmlentities($member['id']); ?>" class="btn btn-sm btn-primary">
                                                <i class="fa fa-line-chart"></i> View Progress
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted">No registered members found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <?php include_once 'include/footer.php'; ?>

    <!-- Essential javascripts for application to work-->
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="js/plugins/pace.min.js"></script>

    <?php if($member_info): ?>
    <script>
    var memberId = <?php echo $member_id; ?>;
    var goalsChart = null;
    var workoutChart = null;

    $(document).ready(function() {
        setupCharts();
        loadMemberData();
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

    function loadMemberData() {
        $.ajax({
            url: 'view-member-progress-data.php',
            method: 'POST',
            data: {
                action: 'get_member_data',
                member_id: memberId
            },
            dataType: 'json',
            success: function(data) {
                if(data.error) {
                    console.error("Server error:", data.error);
                    return;
                }

                $('#active-goals-count').text(data.goals_summary ? data.goals_summary.active_goals : 0);
                $('#completed-goals-count').text(data.goals_summary ? data.goals_summary.completed_goals : 0);
                $('#total-workouts-count').text(data.workout_summary ? data.workout_summary.total_workouts : 0);

                $('#total-duration').text((data.workout_summary ? data.workout_summary.total_duration : 0) + ' min');
                $('#total-calories').text((data.workout_summary ? data.workout_summary.total_calories : 0) + ' kcal');

                // Overall progress percentage
                var active = data.goals_summary ? parseInt(data.goals_summary.active_goals) : 0;
                var completed = data.goals_summary ? parseInt(data.goals_summary.completed_goals) : 0;
                var total = active + completed;
                var overallPercent = total > 0 ? Math.round((completed / total) * 100) : 0;
                $('#overall-progress-bar').css('width', overallPercent + '%').text(overallPercent + '%');

                if(goalsChart && data.goals_chart_data) {
                    goalsChart.data.datasets[0].data = [data.goals_chart_data.completed || 0, data.goals_chart_data.in_progress || 0];
                    goalsChart.update();
                }

                if(workoutChart && data.workout_chart_data) {
                    workoutChart.data.labels = data.workout_chart_data.labels || [];
                    workoutChart.data.datasets[0].data = data.workout_chart_data.data || [];
                    workoutChart.update();
                }

                loadGoalsTable(data.goals_list || []);
                loadWorkoutsTable(data.workouts_list || []);
            },
            error: function(xhr, status, error) {
                console.error("Error fetching member progress data:", error);
            }
        });
    }

    function loadGoalsTable(goalsList) {
        var tbody = $('#goals-table-body');
        tbody.empty();

        if(!goalsList || goalsList.length === 0) {
            tbody.append('<tr><td colspan="7" class="text-center text-muted">No goals found for this member.</td></tr>');
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

            var targetDate = new Date(goal.target_date);
            var today = new Date();
            var timeDiff = targetDate.getTime() - today.getTime();
            var daysRemaining = Math.ceil(timeDiff / (1000 * 3600 * 24));
            var daysText = daysRemaining > 0 ? daysRemaining + ' days' : (daysRemaining === 0 ? 'Today' : 'Expired');

            var row = '<tr>' +
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
                '<td>' + $('<div>').text(daysText).html() + '</td>' +
                '</tr>';
            tbody.append(row);
        });
    }

    function loadWorkoutsTable(workoutsList) {
        var tbody = $('#workouts-table-body');
        tbody.empty();

        if(!workoutsList || workoutsList.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">No workout logs found for this member.</td></tr>');
            return;
        }

        workoutsList.forEach(function(workout) {
            var row = '<tr>' +
                '<td>' + $('<div>').text(workout.workout_date).html() + '</td>' +
                '<td>' + $('<div>').text((workout.duration || 0) + ' min').html() + '</td>' +
                '<td>' + $('<div>').text((workout.calories_burned || 0) + ' kcal').html() + '</td>' +
                '<td><span class="badge badge-info">' + (workout.exercise_count || 0) + ' exercises</span></td>' +
                '<td>' + $('<div>').text(workout.notes || '-').html() + '</td>' +
                '</tr>';
            tbody.append(row);
        });
    }
    </script>
    <?php endif; ?>
</body>
</html>
<?php } ?>