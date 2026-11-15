
<footer class="lp-footer">
  <div class="lp-footer-glow"></div>
  <div class="lp-container">
    <div class="lp-footer-grid">

      <!-- Brand col -->
      <div class="lp-footer-brand">
        <div class="lp-footer-wordmark">
          <span class="lp-footer-abbr">KRK</span>
          <div>
            <div class="lp-footer-name">KRK Polytechnic</div>
            <div class="lp-footer-tagline">Hostel Allocation Portal</div>
          </div>
        </div>
        <p>Seamless hostel registration and room allocation for students of KRK Polytechnic, Lagos.</p>
        <div class="lp-footer-badges">
          <span><i class="fa fa-shield-alt"></i> Secure</span>
          <span><i class="fa fa-bolt"></i> Fast</span>
          <span><i class="fa fa-mobile-alt"></i> Mobile Ready</span>
        </div>
      </div>

      <!-- Student portal -->
      <div class="lp-footer-col">
        <h5>Student Portal</h5>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/student/login.php"><i class="fa fa-sign-in-alt"></i> Student Login</a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/student/register.php"><i class="fa fa-user-plus"></i> Register</a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/student/forgot_password.php"><i class="fa fa-key"></i> Forgot Password</a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/student/resend_activation.php"><i class="fa fa-envelope"></i> Resend Activation</a>
      </div>

      <!-- Admin -->
      <div class="lp-footer-col">
        <h5>Admin Access</h5>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/login.php"><i class="fa fa-user-shield"></i> DSA Login</a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/login.php"><i class="fa fa-user-tie"></i> Registrar Login</a>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/admin/login.php"><i class="fa fa-laptop-code"></i> ICT Staff Login</a>
      </div>

      <!-- Quick links -->
      <div class="lp-footer-col">
        <h5>Quick Links</h5>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/index.php"><i class="fa fa-home"></i> Home</a>
        <a href="#"><i class="fa fa-bed"></i> 200 Bed Spaces</a>
        <a href="#"><i class="fa fa-info-circle"></i> Eligibility Info</a>
        <a href="#"><i class="fa fa-bell"></i> Notifications</a>
      </div>
    </div>

    <div class="lp-footer-bottom">
      <p>&copy; <?= date('Y') ?> KRK Polytechnic, Lagos. All rights reserved.</p>
      <p>Developed by CSC 445 Group 2 &middot; Secure · Transparent · Digital</p>
    </div>
  </div>
</footer>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/assets/js/main.js"></script>
</body>
</html>
