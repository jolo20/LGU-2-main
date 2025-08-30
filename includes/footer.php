    </main>
  </div>

  <!-- Logout Confirmation Modal -->
  <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="logoutConfirmLabel">Confirm logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p class="mb-0">Are you sure you want to log out?</p>
        </div>

        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="confirmLogoutBtn" class="btn btn-danger btn-sm">Logout</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Footer -->
  <footer class="footer">© <span id="year"></span>&nbsp;Local Government Unit 2 — All rights reserved</footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $root ?>assets/js/script.js"></script>
  <script>
    document.getElementById('confirmLogoutBtn').addEventListener('click', function() {
      window.location.href = '<?= $root ?>logout.php';
    });
  </script>
</body>
</html>
