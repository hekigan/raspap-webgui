<?php

include_once('/var/raspap/html/includes/status_messages.php');

function DisplayAuthConfig($username, $password)
{
    $status = new StatusMessages();
    if (isset($_POST['UpdateAdminPassword'])) {
        if (CSRFValidate()) {
            if (password_verify($_POST['oldpass'], $password)) {
                $new_username=trim($_POST['username']);
                if ($_POST['newpass'] !== $_POST['newpassagain']) {
                    $status->addMessage('New passwords do not match', 'danger');
                } elseif ($new_username == '') {
                    $status->addMessage('Username must not be empty', 'danger');
                } else {
                    if (!file_exists(RASPI_ADMIN_DETAILS)) {
                        $tmpauth = fopen(RASPI_ADMIN_DETAILS, 'w');
                        fclose($tmpauth);
                    }

                    if ($auth_file = fopen(RASPI_ADMIN_DETAILS, 'w')) {
                        fwrite($auth_file, $new_username.PHP_EOL);
                        fwrite($auth_file, password_hash($_POST['newpass'], PASSWORD_BCRYPT).PHP_EOL);
                        fclose($auth_file);
                        $username = $new_username;
                        $status->addMessage('Admin password updated');
                    } else {
                        $status->addMessage('Failed to update admin password', 'danger');
                    }
                }
            } else {
                $status->addMessage('Old password does not match', 'danger');
            }
        } else {
            error_log('CSRF violation');
        }
    }
?>
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-primary">
        <div class="panel-heading"><i class="fa fa-lock fa-fw"></i><?php echo _("Configure Auth"); ?></div>
        <div class="panel-body">
          <p><?php $status->showMessages(); ?></p>
          <form role="form" action="?page=auth_conf" method="POST">
            <?php CSRFToken() ?>
            <div class="row">
              <div class="form-group col-md-4">
                <label for="username"><?php echo _("Username"); ?></label>
                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES); ?>"/>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-4">
                <label for="password"><?php echo _("Old password"); ?></label>
                <input type="password" class="form-control" name="oldpass"/>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-4">
                <label for="password"><?php echo _("New password"); ?></label>
                <input type="password" class="form-control" name="newpass"/>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-4">
                <label for="password"><?php echo _("Repeat new password"); ?></label>
                <input type="password" class="form-control" name="newpassagain"/>
              </div>
            </div>
            <input type="submit" class="btn btn-outline btn-primary" name="UpdateAdminPassword" value="<?php echo _("Save settings"); ?>" />
          </form>
        </div><!-- /.panel-body -->
      </div><!-- /.panel-default -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->
<?php
}

