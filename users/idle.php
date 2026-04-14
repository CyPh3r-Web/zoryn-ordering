
<div id="idleModal">
    <div class="idle-modal-content">
        <h2>Are you still there?</h2>
        <p>You have been inactive for a while.</p>
        <p>You will be logged out in <span id="countdown">15</span> seconds.</p>
        <button onclick="resetIdleTimer()">Yes, I'm here</button>
        <button onclick="logout()">Logout now</button>
    </div>
</div>

<style>
    #idleModal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 2000;
    }

    .idle-modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        text-align: center;
        max-width: 400px;
        width: 90%;
    }

    .idle-modal-content h2 {
        color: #33186B;
        margin-bottom: 20px;
    }

    .idle-modal-content button {
        background-color: #5C4033;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        margin: 10px;
    }

    .idle-modal-content button:hover {
        background-color: #5C4033;
    }

    #countdown {
        font-weight: bold;
        color: #ff0000;
    }
</style>

<script>
    let idleTime = 0;
    let idleInterval;
    let countdownInterval;
    let warningTimeout;
    const idleTimeout = 35000; // 1 minute in milliseconds
    const warningTime = 15000; // 15 seconds warning before logout

    function startIdleTimer() {
        // Reset idle time on user activity
        document.onmousemove = resetIdleTime;
        document.onkeypress = resetIdleTime;
        document.onmousedown = resetIdleTime;
        document.ontouchstart = resetIdleTime;
        document.onclick = resetIdleTime;
        document.onscroll = resetIdleTime;

        // Start the idle timer
        idleInterval = setInterval(checkIdleTime, 1000);
    }

    function resetIdleTime() {
        idleTime = 0;
        clearTimeout(warningTimeout);
        document.getElementById('idleModal').style.display = 'none';
        clearInterval(countdownInterval);
    }

    function checkIdleTime() {
        idleTime += 1000;
        
        // Show warning at 45 seconds (15 seconds before timeout)
        if (idleTime >= idleTimeout - warningTime && document.getElementById('idleModal').style.display !== 'block') {
            showWarning();
        }
    }

    function showWarning() {
        document.getElementById('idleModal').style.display = 'block';
        let countdown = 15; // 15 second countdown
        
        countdownInterval = setInterval(() => {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            
            if (countdown <= 0) {
                logout();
            }
        }, 1000);

        // Set timeout for final logout
        warningTimeout = setTimeout(logout, warningTime);
    }

    function resetIdleTimer() {
        resetIdleTime();
        startIdleTimer();
    }

    function logout() {
        // Clear all intervals
        clearInterval(idleInterval);
        clearInterval(countdownInterval);
        clearTimeout(warningTimeout);
        
        // Perform logout actions
        fetch('logout.php')
            .then(() => {
                // Redirect to login page
                window.location.href = 'login.php';
            })
            .catch(error => {
                console.error('Logout failed:', error);
                window.location.href = 'login.php'; // Redirect anyway
            });
    }

    // Start the idle timer when the page loads
    document.addEventListener('DOMContentLoaded', startIdleTimer);
</script>