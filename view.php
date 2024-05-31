<?php

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/mod/aichat/view.php', array('id' => $id));
$PAGE->set_title('AI Chat');
$PAGE->set_heading('AI Chat');

echo $OUTPUT->header();
?>

<div id="disclaimer"></div>
<div id="root"></div>
<div id="clear_text" style="display: none">Clear text</div>
<div id="ref_id" style="display: none">1</div>
<div id="url" style="display: none"><?php echo $CFG->wwwroot . '/mod/aichat/api.php'; ?></div>

<script src="amd/js/index.js"></script>

<?php
echo $OUTPUT->footer();
