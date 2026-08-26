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
    <title>Gym Management System | Workout Logging</title>
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
        .workout-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            padding: 25px;
            margin-bottom: 30px;
        }
        .workout-header {
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
        .exercise-row {
            background: #fdfdfd;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
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
                    <h2>Workout Logging</h2>
                    <p>Track your daily workouts, sets, reps, and burned calories</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Section -->
    <section class="pricing-section spad">
        <div class="container">
            <!-- Log Workout Form -->
            <div class="workout-card">
                <div class="workout-header"><i class="fa fa-pencil-square-o text-danger"></i> Log a New Workout Session</div>
                <form id="workout-form">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="workout_date"><b>Workout Date:</b></label>
                            <input type="date" class="form-control" id="workout_date" name="workout_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="duration"><b>Duration (Minutes):</b></label>
                            <input type="number" class="form-control" id="duration" name="duration" placeholder="e.g. 45" min="1" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="calories_burned"><b>Calories Burned (kcal):</b></label>
                            <input type="number" class="form-control" id="calories_burned" name="calories_burned" placeholder="e.g. 350" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes"><b>Workout Notes / Comments (Optional):</b></label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="e.g., Chest and Triceps focus, felt strong today!"></textarea>
                    </div>

                    <!-- Exercises List Container -->
                    <div class="mt-4 mb-2 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 font-weight-bold text-dark"><i class="fa fa-list-ol text-primary"></i> Exercises Performed</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-exercise-btn"><i class="fa fa-plus"></i> Add Exercise</button>
                    </div>

                    <div id="exercises-container" class="mt-2">
                        <div class="exercise-row">
                            <div class="form-row align-items-center">
                                <div class="col-md-4 form-group mb-2 mb-md-0">
                                    <label class="small text-muted font-weight-bold mb-1">Exercise Name</label>
                                    <input type="text" class="form-control form-control-sm ex-name" placeholder="e.g. Bench Press" required>
                                </div>
                                <div class="col-md-2 form-group mb-2 mb-md-0">
                                    <label class="small text-muted font-weight-bold mb-1">Sets</label>
                                    <input type="number" class="form-control form-control-sm ex-sets" placeholder="Sets" min="1" required>
                                </div>
                                <div class="col-md-2 form-group mb-2 mb-md-0">
                                    <label class="small text-muted font-weight-bold mb-1">Reps</label>
                                    <input type="number" class="form-control form-control-sm ex-reps" placeholder="Reps" min="1" required>
                                </div>
                                <div class="col-md-3 form-group mb-2 mb-md-0">
                                    <label class="small text-muted font-weight-bold mb-1">Weight (kg)</label>
                                    <input type="number" step="0.5" class="form-control form-control-sm ex-weight" placeholder="Weight (kg)">
                                </div>
                                <div class="col-md-1 form-group mb-0 text-center">
                                    <label class="small text-muted d-none d-md-block mb-1">&nbsp;</label>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-ex-btn" disabled><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-right">
                        <button type="submit" class="btn btn-theme"><i class="fa fa-save"></i> Save Workout Log</button>
                    </div>
                </form>
                <div id="form-message" class="mt-2"></div>
            </div>

            <!-- Workout History & Frequency Chart Row -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="workout-card">
                        <div class="workout-header"><i class="fa fa-history text-primary"></i> Workout History</div>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Duration</th>
                                        <th>Calories</th>
                                        <th>Exercises</th>
                                        <th>Notes</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="workouts-table-body">
                                    <tr><td colspan="6" class="text-center text-muted">Loading workouts...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="workout-card">
                        <div class="workout-header"><i class="fa fa-bar-chart text-success"></i> Workout Frequency</div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="workoutFrequencyChart"></canvas>
                        </div>
                        <div class="mt-3 text-center text-muted">
                            <small>Recent daily workout consistency (last 7 recorded days)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Workout Details Modal -->
    <div class="modal fade" id="workoutModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="workoutModalLabel"><i class="fa fa-info-circle text-primary"></i> Workout Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <span class="text-muted small d-block">Date:</span>
                            <strong id="modal-date">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Duration:</span>
                            <strong id="modal-duration">-</strong>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <span class="text-muted small d-block">Calories:</span>
                            <strong id="modal-calories">-</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted small d-block">Notes:</span>
                            <span id="modal-notes" class="text-dark">-</span>
                        </div>
                    </div>
                    <hr>
                    <h6 class="font-weight-bold mb-2">Exercise Breakdown:</h6>
                    <ul class="list-group" id="modal-exercises-list">
                        <li class="list-group-item text-muted">No exercises found</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
    var workoutChart = null;

    $(document).ready(function() {
        setupWorkoutChart();
        loadWorkoutsData();

        $('#workout-form').submit(function(e) {
            e.preventDefault();
            submitWorkoutForm();
        });

        $('#add-exercise-btn').click(function() {
            addExerciseRow();
        });

        $(document).on('click', '.remove-ex-btn', function() {
            if ($('.exercise-row').length > 1) {
                $(this).closest('.exercise-row').remove();
                checkRemoveButtons();
            }
        });
    });

    function addExerciseRow() {
        var rowHtml = '<div class="exercise-row">' +
            '<div class="form-row align-items-center">' +
                '<div class="col-md-4 form-group mb-2 mb-md-0">' +
                    '<input type="text" class="form-control form-control-sm ex-name" placeholder="e.g. Squats" required>' +
                '</div>' +
                '<div class="col-md-2 form-group mb-2 mb-md-0">' +
                    '<input type="number" class="form-control form-control-sm ex-sets" placeholder="Sets" min="1" required>' +
                '</div>' +
                '<div class="col-md-2 form-group mb-2 mb-md-0">' +
                    '<input type="number" class="form-control form-control-sm ex-reps" placeholder="Reps" min="1" required>' +
                '</div>' +
                '<div class="col-md-3 form-group mb-2 mb-md-0">' +
                    '<input type="number" step="0.5" class="form-control form-control-sm ex-weight" placeholder="Weight (kg)">' +
                '</div>' +
                '<div class="col-md-1 form-group mb-0 text-center">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-ex-btn"><i class="fa fa-times"></i></button>' +
                '</div>' +
            '</div>' +
        '</div>';
        $('#exercises-container').append(rowHtml);
        checkRemoveButtons();
    }

    function checkRemoveButtons() {
        var rows = $('.exercise-row');
        if (rows.length === 1) {
            rows.find('.remove-ex-btn').prop('disabled', true);
        } else {
            rows.find('.remove-ex-btn').prop('disabled', false);
        }
    }

    function setupWorkoutChart() {
        var ctx = document.getElementById('workoutFrequencyChart').getContext('2d');
        workoutChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Workouts',
                    data: [],
                    backgroundColor: '#f65d5d'
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

    function loadWorkoutsData() {
        $.ajax({
            url: 'workout-logging-data.php',
            method: 'POST',
            data: {
                action: 'get_workouts',
                user_id: userId
            },
            dataType: 'json',
            success: function(data) {
                loadWorkoutsTable(data.workouts_list || []);
                if (workoutChart && data.workout_chart_data) {
                    workoutChart.data.labels = data.workout_chart_data.labels || [];
                    workoutChart.data.datasets[0].data = data.workout_chart_data.data || [];
                    workoutChart.update();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading workouts:", error);
            }
        });
    }

    function submitWorkoutForm() {
        var workout_date = $('#workout_date').val();
        var duration = $('#duration').val();
        var calories_burned = $('#calories_burned').val();
        var notes = $('#notes').val();

        if (!workout_date || !duration || duration <= 0) {
            showFormMessage('Please enter valid workout date and duration', 'warning');
            return;
        }

        var exercises = [];
        var valid = true;
        $('.exercise-row').each(function() {
            var name = $(this).find('.ex-name').val().trim();
            var sets = $(this).find('.ex-sets').val();
            var reps = $(this).find('.ex-reps').val();
            var weight = $(this).find('.ex-weight').val();

            if (!name || !sets || !reps) {
                valid = false;
                return false;
            }

            exercises.push({
                name: name,
                sets: sets,
                reps: reps,
                weight: weight || 0
            });
        });

        if (!valid || exercises.length === 0) {
            showFormMessage('Please complete all exercise fields (name, sets, reps)', 'warning');
            return;
        }

        $.ajax({
            url: 'workout-logging-data.php',
            method: 'POST',
            data: {
                action: 'add_workout',
                workout_date: workout_date,
                duration: duration,
                calories_burned: calories_burned || 0,
                notes: notes,
                exercises: exercises
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showFormMessage('Workout logged successfully!', 'success');
                    $('#workout-form')[0].reset();
                    $('#workout_date').val(new Date().toISOString().split('T')[0]);
                    // Reset to single exercise row
                    var firstRow = $('.exercise-row:first');
                    $('#exercises-container').html(firstRow);
                    firstRow.find('input').val('');
                    checkRemoveButtons();
                    loadWorkoutsData();
                } else {
                    showFormMessage(res.message || 'Error saving workout', 'danger');
                }
            },
            error: function() {
                showFormMessage('Server error occurred while saving workout', 'danger');
            }
        });
    }

    function loadWorkoutsTable(workoutsList) {
        var tbody = $('#workouts-table-body');
        tbody.empty();

        if (!workoutsList || workoutsList.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">You have not logged any workouts yet.</td></tr>');
            return;
        }

        workoutsList.forEach(function(workout) {
            var row = '<tr>' +
                '<td><strong>' + $('<div>').text(workout.workout_date).html() + '</strong></td>' +
                '<td>' + $('<div>').text((workout.duration || 0) + ' min').html() + '</td>' +
                '<td>' + $('<div>').text((workout.calories_burned || 0) + ' kcal').html() + '</td>' +
                '<td><span class="badge badge-info">' + (workout.exercise_count || 0) + ' exercises</span></td>' +
                '<td>' + $('<div>').text(workout.notes || '-').html() + '</td>' +
                '<td>' +
                    '<button class="btn btn-sm btn-outline-primary view-workout-btn" data-id="' + workout.id + '"><i class="fa fa-eye"></i> Details</button>' +
                '</td>' +
                '</tr>';
            tbody.append(row);
        });

        $('.view-workout-btn').on('click', function() {
            var workoutId = $(this).data('id');
            viewWorkoutDetails(workoutId);
        });
    }

    function viewWorkoutDetails(workoutId) {
        $.ajax({
            url: 'workout-logging-data.php',
            method: 'POST',
            data: {
                action: 'get_workout_details',
                workout_id: workoutId
            },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.workout) {
                    var w = res.workout;
                    $('#modal-date').text(w.workout_date || '-');
                    $('#modal-duration').text((w.duration || 0) + ' minutes');
                    $('#modal-calories').text((w.calories_burned || 0) + ' kcal');
                    $('#modal-notes').text(w.notes || 'None');

                    var list = $('#modal-exercises-list');
                    list.empty();
                    if (w.exercises && w.exercises.length > 0) {
                        w.exercises.forEach(function(ex) {
                            var exName = ex.exercise_name || ex.name || 'Exercise';
                            var weightStr = ex.weight > 0 ? ' @ ' + ex.weight + ' kg' : '';
                            list.append('<li class="list-group-item d-flex justify-content-between align-items-center">' +
                                '<span><strong>' + $('<div>').text(exName).html() + '</strong>' + weightStr + '</span>' +
                                '<span class="badge badge-secondary badge-pill">' + ex.sets + ' sets × ' + ex.reps + ' reps</span>' +
                            '</li>');
                        });
                    } else {
                        list.append('<li class="list-group-item text-muted">No exercises detailed</li>');
                    }

                    $('#workoutModal').modal('show');
                } else {
                    alert('Could not load workout details');
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