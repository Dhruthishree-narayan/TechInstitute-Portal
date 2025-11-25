<?php
/*
 * SINGLE FILE PHP REGISTRATION APP
 * --------------------------------
 * This script handles:
 * 1. Database creation (automatically sets up 'admission_db' and 'applications' table).
 * 2. API endpoints for saving and retrieving data via AJAX.
 * 3. Serving the Frontend HTML/JS interface.
 */

// --- CONFIGURATION ---
$servername = "localhost";
$username   = "root";      // Default XAMPP username
$password   = "";          // Default XAMPP password (empty)
$dbname     = "admission_db";

// --- BACKEND LOGIC ---
// Only run PHP logic if it's an API request or initial load.
// We suppress warnings to prevent messing up JSON output, but in production, handle errors gracefully.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// 1. Connect to MySQL (Create DB if not exists)
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create Database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
$conn->query($sql);
$conn->select_db($dbname);

// Create Table if it doesn't exist
$tableSql = "CREATE TABLE IF NOT EXISTS applications (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ref_id VARCHAR(30) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    dob DATE NOT NULL,
    course VARCHAR(50) NOT NULL,
    session_time VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($tableSql);

// 2. Handle API Requests (AJAX)
// We check for a query parameter 'action' to distinguish API calls from page loads
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // ACTION: SUBMIT FORM
    if ($_GET['action'] === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $refId = 'APP-' . mt_rand(100000, 999999);
        
        $stmt = $conn->prepare("INSERT INTO applications (ref_id, fullname, email, phone, gender, dob, course, session_time, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Bind parameters
        $stmt->bind_param("sssssssss", 
            $refId,
            $_POST['fullname'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['gender'],
            $_POST['dob'],
            $_POST['course'],
            $_POST['session'],
            $_POST['address']
        );

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "refId" => $refId, 
                "date" => date("M j, Y H:i"),
                "data" => $_POST
            ]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }
        $stmt->close();
        exit(); // Stop script execution so we don't return HTML
    }

    // ACTION: FETCH ALL
    if ($_GET['action'] === 'fetch') {
        $result = $conn->query("SELECT * FROM applications ORDER BY id DESC");
        $rows = [];
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode($rows);
        exit();
    }

    // ACTION: CLEAR DATA
    if ($_GET['action'] === 'clear') {
        $conn->query("TRUNCATE TABLE applications");
        echo json_encode(["success" => true]);
        exit();
    }
}
?>

<!-- FRONTEND HTML START -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechInstitute Portal</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        slate: {
                            850: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.4s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }

        @media print {
            body { background-color: white !important; color: black !important; }
            nav, .no-print { display: none !important; }
            #result-section { 
                display: block !important; 
                position: static; 
                width: 100%; 
                border: none !important; 
                box-shadow: none !important;
                background: white !important;
            }
            #result-section * { color: black !important; visibility: visible !important; }
            .print-border { border: 2px solid #000 !important; }
        }
    </style>
</head>
<body class="bg-slate-950 min-h-screen text-slate-300 flex flex-col">

    <!-- Navbar -->
    <nav class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50 shadow-md">
        <div class="container mx-auto px-4 h-16 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="font-bold text-lg text-white tracking-tight">TechInstitute<span class="text-blue-500">Portal</span></span>
            </div>
            <div class="flex items-center gap-3">
                <button id="viewAppsBtn" class="text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 px-4 py-2 rounded-md transition-all flex items-center gap-2">
                    <i class="fas fa-columns"></i> 
                    <span>Admin Dashboard</span>
                </button>
                <button id="closeAdminBtn" class="hidden text-xs font-bold bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 px-4 py-2 rounded-md transition-all flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    <span>Close Admin</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8 max-w-5xl">

        <!-- REGISTRATION FORM -->
        <div id="form-section" class="fade-in">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">New Application</h1>
                <p class="text-slate-500 text-sm">Fill out the details below to register for the upcoming academic session.</p>
            </div>

            <div class="bg-slate-900 rounded-xl border border-slate-800 shadow-2xl overflow-hidden">
                <div class="h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-blue-500"></div>

                <form id="registrationForm" class="p-8 space-y-8">
                    
                    <!-- Section: Personal Info -->
                    <div>
                        <h3 class="text-sm uppercase tracking-wider text-slate-500 font-bold mb-5 flex items-center gap-2">
                            <i class="fas fa-user-circle text-blue-500"></i> Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="relative group">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 ml-1">Full Name</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <input type="text" name="fullname" required 
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white placeholder-slate-600 outline-none transition-all" 
                                        placeholder="Jane Doe">
                                </div>
                            </div>
                            <div class="relative group">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 ml-1">Date of Birth</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <input type="date" name="dob" required 
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white outline-none transition-all [color-scheme:dark]">
                                </div>
                            </div>
                            <div class="relative group">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 ml-1">Email Address</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <input type="email" name="email" required 
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white placeholder-slate-600 outline-none transition-all" 
                                        placeholder="jane@example.com">
                                </div>
                            </div>
                            <div class="relative group">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 ml-1">Phone Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <input type="tel" name="phone" required 
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white placeholder-slate-600 outline-none transition-all" 
                                        placeholder="+1 (555) 000-0000">
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-400 mb-2 ml-1">Gender</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center p-3 border border-slate-700 rounded-lg cursor-pointer bg-slate-800/50 hover:bg-slate-800 hover:border-slate-600 transition-all flex-1 justify-center">
                                        <input type="radio" name="gender" value="Male" class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 focus:ring-blue-500 focus:ring-offset-slate-900" checked>
                                        <span class="ml-2 text-sm">Male</span>
                                    </label>
                                    <label class="flex items-center p-3 border border-slate-700 rounded-lg cursor-pointer bg-slate-800/50 hover:bg-slate-800 hover:border-slate-600 transition-all flex-1 justify-center">
                                        <input type="radio" name="gender" value="Female" class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 focus:ring-blue-500 focus:ring-offset-slate-900">
                                        <span class="ml-2 text-sm">Female</span>
                                    </label>
                                    <label class="flex items-center p-3 border border-slate-700 rounded-lg cursor-pointer bg-slate-800/50 hover:bg-slate-800 hover:border-slate-600 transition-all flex-1 justify-center">
                                        <input type="radio" name="gender" value="Other" class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 focus:ring-blue-500 focus:ring-offset-slate-900">
                                        <span class="ml-2 text-sm">Other</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-800">

                    <!-- Section: Academic Details -->
                    <div>
                        <h3 class="text-sm uppercase tracking-wider text-slate-500 font-bold mb-5 flex items-center gap-2">
                            <i class="fas fa-book text-blue-500"></i> Academic Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 ml-1">Select Program</label>
                                <div class="relative">
                                    <select name="course" class="w-full pl-4 pr-10 py-2.5 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white outline-none appearance-none transition-all">
                                        <option value="Web Development">Web Development</option>
                                        <option value="Data Science">Data Science</option>
                                        <option value="UI/UX Design">UI/UX Design</option>
                                        <option value="Cyber Security">Cyber Security</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 ml-1">Preferred Session</label>
                                <div class="relative">
                                    <select name="session" class="w-full pl-4 pr-10 py-2.5 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white outline-none appearance-none transition-all">
                                        <option value="Morning">Morning (9 AM - 1 PM)</option>
                                        <option value="Afternoon">Afternoon (2 PM - 6 PM)</option>
                                        <option value="Weekend">Weekend Batch</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-400 mb-1.5 ml-1">Residential Address</label>
                                <textarea name="address" rows="2" required 
                                    class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 text-white placeholder-slate-600 outline-none transition-all resize-none"
                                    placeholder="Street address, City, State, Zip"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Submit -->
                    <div class="pt-2">
                        <div class="flex items-center mb-6">
                            <input type="checkbox" id="terms" required class="w-4 h-4 text-blue-600 bg-slate-800 border-slate-700 rounded focus:ring-blue-500 focus:ring-offset-slate-900">
                            <label for="terms" class="ml-2 text-sm text-slate-400">I verify that the information provided is accurate and agree to the <a href="#" class="text-blue-400 hover:underline">Terms of Service</a>.</label>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 px-4 rounded-lg shadow-lg shadow-blue-500/20 transition-all transform active:scale-[0.99] flex justify-center items-center gap-2 group">
                            <span>Submit Application</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SUCCESS RECEIPT SECTION (Hidden) -->
        <div id="result-section" class="hidden fade-in max-w-2xl mx-auto">
            <div class="bg-slate-900 rounded-xl border border-slate-800 shadow-2xl overflow-hidden print-border relative">
                <div class="bg-green-600/10 border-b border-green-500/20 p-6 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mb-3 shadow-lg shadow-green-500/30">
                        <i class="fas fa-check text-2xl text-white"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Registration Complete</h2>
                    <p class="text-green-400 text-sm mt-1">Application Submitted Successfully</p>
                </div>

                <div class="p-8">
                    <div class="border border-slate-700 rounded-lg bg-slate-800/30 p-6 relative overflow-hidden">
                        <div class="absolute -right-10 -bottom-10 text-9xl text-slate-700/10 transform -rotate-12 pointer-events-none">
                            <i class="fas fa-stamp"></i>
                        </div>

                        <div class="flex justify-between items-start border-b border-slate-700/50 pb-4 mb-6">
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Application ID</p>
                                <p id="display-ref" class="text-xl font-mono text-blue-400 tracking-wide">APP-000000</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-500 uppercase font-bold tracking-wider mb-1">Date</p>
                                <p id="display-date" class="text-sm text-white">-</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold mb-1">Applicant Name</p>
                                <p id="display-name" class="text-sm font-medium text-white">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold mb-1">Selected Program</p>
                                <p id="display-course" class="text-sm font-medium text-white">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold mb-1">Contact Email</p>
                                <p id="display-email" class="text-sm text-slate-300">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold mb-1">Contact Phone</p>
                                <p id="display-phone" class="text-sm text-slate-300">-</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold mb-1">Session</p>
                                <p id="display-session" class="text-sm text-slate-300">-</p>
                            </div>
                             <div>
                                <p class="text-xs text-slate-500 uppercase font-bold mb-1">Gender</p>
                                <p id="display-gender" class="text-sm text-slate-300">-</p>
                            </div>
                            <div class="col-span-2 mt-2 pt-4 border-t border-slate-700/50">
                                <p class="text-xs text-slate-500 uppercase font-bold mb-1">Address</p>
                                <p id="display-address" class="text-sm text-slate-300">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 border-t border-slate-800 p-6 flex flex-col sm:flex-row gap-3 justify-center no-print">
                    <button onclick="window.print()" class="flex-1 bg-white hover:bg-slate-200 text-slate-900 font-bold py-2.5 px-6 rounded-lg shadow transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <button id="resetBtn" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 font-medium py-2.5 px-6 rounded-lg transition-colors">
                        New Application
                    </button>
                </div>
            </div>
        </div>

        <!-- ADMIN DASHBOARD SECTION (Hidden) -->
        <div id="admin-section" class="hidden fade-in">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-white">Dashboard</h2>
                    <p class="text-slate-500 text-sm">Manage student applications (Live from Database)</p>
                </div>
                <button id="clearDataBtn" class="text-xs text-red-400 hover:text-red-300 hover:bg-red-500/10 px-3 py-1.5 rounded border border-red-500/20 transition-colors">
                    <i class="fas fa-trash-alt mr-1"></i> Clear Database
                </button>
            </div>

            <div class="bg-slate-900 rounded-xl border border-slate-800 shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-400">
                        <thead class="bg-slate-950 text-xs uppercase font-bold tracking-wider border-b border-slate-800 text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Ref ID</th>
                                <th class="px-6 py-4">Submitted</th>
                                <th class="px-6 py-4">Applicant</th>
                                <th class="px-6 py-4">Program</th>
                                <th class="px-6 py-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody id="applicationsTableBody" class="divide-y divide-slate-800">
                            <!-- JS Injected Rows -->
                        </tbody>
                    </table>
                </div>
                
                <div id="no-data-msg" class="hidden py-16 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-800 mb-4">
                        <i class="fas fa-folder-open text-slate-600 text-2xl"></i>
                    </div>
                    <p class="text-slate-500">No applications found in the database.</p>
                </div>
                
                <!-- Loading State -->
                <div id="loading-msg" class="py-16 text-center">
                    <i class="fas fa-spinner fa-spin text-blue-500 text-3xl"></i>
                    <p class="text-slate-500 mt-2">Loading data from server...</p>
                </div>
            </div>
        </div>

    </main>

    <footer class="border-t border-slate-900 py-6 mt-auto">
        <div class="container mx-auto px-4 text-center text-xs text-slate-600">
            &copy; 2023 TechInstitute. All Rights Reserved.
        </div>
    </footer>

    <!-- LOGIC -->
    <script>
        $(document).ready(function() {
            
            // --- Navigation ---
            function toggleView(view) {
                $('#form-section, #result-section, #admin-section').addClass('hidden');
                
                if (view === 'admin') {
                    loadApplications();
                    $('#admin-section').removeClass('hidden').addClass('fade-in');
                    $('#viewAppsBtn').addClass('hidden');
                    $('#closeAdminBtn').removeClass('hidden');
                } else if (view === 'form') {
                    $('#form-section').removeClass('hidden').addClass('fade-in');
                    $('#closeAdminBtn').addClass('hidden');
                    $('#viewAppsBtn').removeClass('hidden');
                } else if (view === 'result') {
                    $('#result-section').removeClass('hidden').addClass('fade-in');
                    $('#closeAdminBtn').addClass('hidden');
                    $('#viewAppsBtn').addClass('hidden');
                }
            }

            $('#viewAppsBtn').click(() => toggleView('admin'));
            $('#closeAdminBtn').click(() => toggleView('form'));
            
            $('#resetBtn').click(function() {
                $('#registrationForm')[0].reset();
                $('button[type="submit"]').prop('disabled', false).find('span').text('Submit Application');
                toggleView('form');
            });


            // --- Form Submission (AJAX POST to PHP) ---
            $('#registrationForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize(); // standard URL encoding
                
                // UI Loading
                const $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).find('span').text('Processing...');

                $.ajax({
                    url: '?action=submit',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Populate Receipt with response data
                            $('#display-ref').text(response.refId);
                            $('#display-date').text(response.date);
                            
                            // Map form data back to display
                            // Note: 'data' in response is the $_POST array
                            const d = response.data;
                            $('#display-name').text(d.fullname);
                            $('#display-course').text(d.course);
                            $('#display-email').text(d.email);
                            $('#display-phone').text(d.phone);
                            $('#display-session').text(d.session);
                            $('#display-gender').text(d.gender);
                            $('#display-address').text(d.address);

                            toggleView('result');
                            $('html, body').scrollTop(0);
                        } else {
                            alert('Database Error: ' + response.error);
                            $btn.prop('disabled', false).find('span').text('Submit Application');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        alert("Error communicating with the server. Ensure index.php is running on a PHP server.");
                        $btn.prop('disabled', false).find('span').text('Submit Application');
                    }
                });
            });

            // --- Database Functions (AJAX GET from PHP) ---
            function loadApplications() {
                $('#loading-msg').show();
                $('#no-data-msg').addClass('hidden');
                $('#applicationsTableBody').empty();

                $.ajax({
                    url: '?action=fetch',
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#loading-msg').hide();
                        
                        if (data.length === 0) {
                            $('#no-data-msg').removeClass('hidden');
                        } else {
                            data.forEach(app => {
                                const row = `
                                    <tr class="group hover:bg-slate-800/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-blue-400 text-xs">${app.ref_id}</td>
                                        <td class="px-6 py-4 text-xs text-slate-500">${app.submission_date}</td>
                                        <td class="px-6 py-4">
                                            <div class="text-white font-medium">${app.fullname}</div>
                                            <div class="text-xs text-slate-500">${app.email}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900/50 text-blue-300 border border-blue-800">
                                                ${app.course}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-green-400 text-xs font-bold flex items-center justify-end gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Paid
                                            </span>
                                        </td>
                                    </tr>
                                `;
                                $('#applicationsTableBody').append(row);
                            });
                        }
                    },
                    error: function() {
                        $('#loading-msg').hide();
                        alert("Could not load data. Ensure you are running this on a PHP server.");
                    }
                });
            }

            $('#clearDataBtn').click(function() {
                if(confirm('Warning: This will permanently delete ALL records from the database. Continue?')) {
                    $.get('?action=clear', function(res) {
                        loadApplications();
                    });
                }
            });

        });
    </script>
</body>
</html>
