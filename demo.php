<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Public demo site of the algorithm plugins (algorithm.tsp.edu.rs).
 *
 * Put this file, algoritam-demo.mbz and algoritam-demo-sr.mbz in the Moodle folder, next to config.php (not in public/,
 * so the web cannot run it), and run it from the command line or a cron job:
 *   php demo.php setup   once: site settings, language packs, demo accounts, both demo courses
 *   php demo.php reset   every night: demo courses from backup again (posts, attempts and changes are gone)
 *
 * @copyright  2026 Boban Blagojević
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
if (file_exists(__DIR__ . '/config.php')) {
    require(__DIR__ . '/config.php');
} else if (file_exists(dirname(__DIR__) . '/config.php')) {
    require(dirname(__DIR__) . '/config.php');
} else {
    require('config.php');
}
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/filterlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

const DEMO_SHORTNAME_EN = 'ALGORITHM-DEMO';
const DEMO_FULLNAME_EN = 'Algorithms with flowcharts: demo (English)';
const DEMO_MBZ_EN = 'algoritam-demo.mbz';

const DEMO_SHORTNAME_SR = 'ALGORITHM-DEMO-SR';
const DEMO_FULLNAME_SR = 'Algoritmi sa dijagramima toka: demo (srpski)';
const DEMO_MBZ_SR = 'algoritam-demo-sr.mbz';

const DEMO_SUMMARIES = [
    'en' => 'Interactive algorithmic thinking and flowchart execution in Moodle. Visualize step-by-step flowchart execution, trace variable states, manipulate decision branches, and solve autograded algorithm challenges.',
    'sr' => 'Interaktivno algoritamsko razmišljanje i izvršavanje dijagrama toka u Moodle-u. Vizuelno pratite izvršavanje korak po korak, promene vrednosti promenljivih, grananja i rešavajte automatski ocenjivane zadatke.',
];

const DEMO_CARDS = [
    'en' => 'cards/card-en.png',
    'sr' => 'cards/card-sr.png',
];

const DEMO_PASSWORD = 'Demo-2026';
const DEMO_USERS = [
    'teacher' => ['firstname' => 'Demo', 'lastname' => 'Teacher', 'role' => 'editingteacher'],
    'student' => ['firstname' => 'Demo', 'lastname' => 'Student', 'role' => 'student'],
];

$command = $argv[1] ?? '';
if (!in_array($command, ['setup', 'reset'], true)) {
    fwrite(STDERR, "Usage: php demo.php setup|reset\n");
    exit(1);
}
\core\session\manager::set_user(get_admin());

// Settings and CSS are always updated so design refinements apply on reset too.
demo_settings();
if ($command === 'setup') {
    demo_languages();
}

demo_cleanup_obsolete_courses();

$en_id = demo_restore_course(DEMO_MBZ_EN, DEMO_SHORTNAME_EN, DEMO_FULLNAME_EN);
$sr_id = demo_restore_course(DEMO_MBZ_SR, DEMO_SHORTNAME_SR, DEMO_FULLNAME_SR);

if ($en_id && $sr_id) {
    demo_section_banner($en_id, 'en', $sr_id);
    demo_section_banner($sr_id, 'sr', $en_id);
} else if ($en_id) {
    demo_section_banner_single($en_id);
} else if ($sr_id) {
    demo_section_banner_single($sr_id);
}

if ($en_id) {
    demo_users($en_id);
    demo_set_course_overview($en_id, DEMO_SUMMARIES['en'], DEMO_CARDS['en']);
}
if ($sr_id) {
    demo_users($sr_id);
    demo_set_course_overview($sr_id, DEMO_SUMMARIES['sr'], DEMO_CARDS['sr']);
}

if ($en_id && $sr_id) {
    demo_frontpage($en_id, $sr_id);
} else {
    demo_frontpage_single($en_id ?: $sr_id);
}

purge_all_caches();
if (file_exists($CFG->dirroot . '/lib/editor/tiny/plugins/codelab/version.php') && !get_config('tiny_codelab', 'jobeapikey')) {
    echo "Still to do: set Jobe API key in Site administration > Plugins > Text editors > TinyMCE editor > CodeLab\n";
}
if ($en_id) {
    echo "Done English: {$CFG->wwwroot}/course/view.php?id={$en_id}\n";
}
if ($sr_id) {
    echo "Done Serbian: {$CFG->wwwroot}/course/view.php?id={$sr_id}\n";
}

/**
 * Site settings of a public demo: no mail, no sign-up, guest button, all content cleaned, the algorithm filter on,
 * the Jobe server of TSP for Run the code (CodeLab), modern custom CSS for theme_boost.
 */
function demo_settings(): void {
    global $CFG;

    $settings = [
        'noemailever' => 1,         // Nothing is ever mailed.
        'registerauth' => '',       // No self registration.
        'guestloginbutton' => 1,    // "Access as a guest" on the login page.
        'forceclean' => 1,          // All content is cleaned, so nobody can put a script on a page.
        'langmenu' => 1,
        'timezone' => 'Europe/Belgrade',
        'maxbytes' => 1048576,
        'messaging' => 0,
        'enableblogs' => 0,
        'enablecourserequests' => 0,
        'defaulthomepage' => 0,
    ];
    foreach ($settings as $name => $value) {
        set_config($name, $value);
    }
    filter_set_global_state('algorithm', TEXTFILTER_ON);
    if (file_exists($CFG->dirroot . '/filter/codelab/version.php')) {
        filter_set_global_state('codelab', TEXTFILTER_ON);
    }
    if (file_exists($CFG->dirroot . '/filter/aisim/version.php')) {
        filter_set_global_state('aisim', TEXTFILTER_ON);
    }
    if (file_exists($CFG->dirroot . '/lib/editor/tiny/plugins/codelab/version.php')) {
        // Run the code of the algorithm plugins compiles on Jobe; the API key is entered by hand (never in this file).
        set_config('jobeserver', 'https://jobe.tsp.edu.rs', 'tiny_codelab');
        set_config('browserpython', 1, 'tiny_codelab');
    }

    // Ensure none of the 3 subplugins are disabled in TinyMCE
    $disabled = get_config('editor_tiny', 'disabledsubplugins') ?: '';
    if (!empty($disabled)) {
        $parts = array_filter(array_map('trim', explode(',', $disabled)), function($p) {
            return !in_array($p, ['algorithm', 'codelab', 'aisim']);
        });
        set_config('disabledsubplugins', implode(',', $parts), 'editor_tiny');
    }

    // Demo login instructions on login/index.php
    $auth_instructions = <<<'HTML'
<div class="card border-0 shadow-lg rounded-4 p-4 text-start text-white" style="background: rgba(15, 23, 42, 0.92) !important; backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.18) !important; max-width: 440px; margin: 0 auto;">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="fs-4">⚡</span>
        <h5 class="fw-bold mb-0 text-white">Instant Demo Access</h5>
    </div>
    <div class="p-3 rounded-3 mb-2" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
        <div class="fw-bold mb-1" style="color: #38bdf8;">🧑‍🏫 Teacher: <code>teacher</code></div>
        <div class="small" style="color: rgba(255, 255, 255, 0.9); line-height: 1.4;">Draw visual flowcharts in TinyMCE, export code to Python, C &amp; Pascal, create interactive quizzes.</div>
    </div>
    <div class="p-3 rounded-3 mb-3" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.12);">
        <div class="fw-bold mb-1" style="color: #fbbf24;">🎓 Student: <code>student</code></div>
        <div class="small" style="color: rgba(255, 255, 255, 0.9); line-height: 1.4;">Step through flowcharts interactively, track variables in the live watch panel, solve algorithm quizzes.</div>
    </div>
    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small mb-2">
        <span style="color: rgba(255, 255, 255, 0.9);">Password:</span>
        <code style="background: #000000; color: #fde047; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 0.95rem; border: 1px solid rgba(253, 224, 71, 0.4);">Demo-2026</code>
    </div>
    <div class="text-center small mt-2" style="color: rgba(255, 255, 255, 0.75);">
        Or click the <strong>Log in as guest</strong> button below!
    </div>
</div>
HTML;
    set_config('auth_instructions', $auth_instructions);

    // Modern Boost and Moove theme custom CSS
    $customcss = <<<'CSS'
/* Modern Boost & Moove Theme Enhancements for Algorithm Demo */
:root {
    --bs-primary: #4f46e5;
    --bs-primary-rgb: 79, 70, 229;
}
body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: #1e293b;
}
.card {
    border-radius: 14px;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}
.demo-course-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.demo-course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 30px rgba(0,0,0,0.12) !important;
}
.badge {
    font-weight: 600;
    letter-spacing: 0.02em;
}
pre, code, kbd, samp {
    font-family: 'Fira Code', Consolas, 'Courier New', monospace;
}
.navbar-bootswatch, .navbar-light {
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
#page-footer {
    background: #0f172a;
    color: #94a3b8;
    padding-top: 2rem;
    padding-bottom: 2rem;
}
#page-footer a {
    color: #818cf8;
}

/* Center login instructions card in Moove theme */
body.pagelayout-login .login-layout-left-content {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
body.pagelayout-login .login-layout-left-content .card {
    max-width: 460px;
    width: 100%;
}
CSS;
    set_config('customcss', $customcss, 'theme_boost');
    set_config('scss', $customcss, 'theme_moove');
    set_config('theme', 'moove');

    // The shared demo accounts cannot change their password, profile or private files.
    $userrole = get_archetype_roles('user');
    $userrole = reset($userrole);
    $system = context_system::instance();
    foreach (['moodle/user:changeownpassword', 'moodle/user:editownprofile', 'moodle/user:manageownfiles'] as $cap) {
        assign_capability($cap, CAP_PROHIBIT, $userrole->id, $system->id, true);
    }
    echo "settings and custom CSS done\n";
}

/**
 * Serbian language packs (Latin and Cyrillic), when the server can download them.
 */
function demo_languages(): void {
    try {
        $controller = new \tool_langimport\controller();
        $controller->install_languagepacks(['sr_lt', 'sr_cr']);
        echo "language packs: " . implode(' ', $controller->info) . "\n";
    } catch (\Throwable $e) {
        echo "language packs not installed: " . $e->getMessage() . "\n";
    }
}

/**
 * Sets the course summary and attaches the custom card cover image to overviewfiles.
 */
function demo_set_course_overview(int $courseid, string $summary, string $cardfile): void {
    global $DB;

    // 1. Set summary
    if ($summary !== '') {
        $DB->set_field('course', 'summary', $summary, ['id' => $courseid]);
        $DB->set_field('course', 'summaryformat', FORMAT_HTML, ['id' => $courseid]);
    }

    // 2. Attach overview card image
    $candidates = [
        __DIR__ . '/' . $cardfile,
        __DIR__ . '/' . basename($cardfile),
    ];
    $found = null;
    foreach ($candidates as $cand) {
        if (file_exists($cand)) {
            $found = $cand;
            break;
        }
    }

    if ($found) {
        try {
            $context = \context_course::instance($courseid);
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'course', 'overviewfiles');
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'course',
                'filearea' => 'overviewfiles',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => basename($found),
            ];
            $fs->create_file_from_pathname($filerecord, $found);
            echo "course {$courseid} overview image set: " . basename($found) . "\n";
        } catch (\Throwable $e) {
            echo "course {$courseid} overview image warning: " . $e->getMessage() . "\n";
        }
    }
}

/**
 * Safely purge obsolete or leftover demo courses (e.g. course ID 2).
 */
function demo_cleanup_obsolete_courses(): void {
    global $DB;

    // List all existing non-site courses
    $courses = $DB->get_records_select('course', 'id > 1');
    echo "Inspecting existing courses in database (" . count($courses) . " found):\n";
    foreach ($courses as $c) {
        echo " - Course ID {$c->id}: shortname='{$c->shortname}', fullname='{$c->fullname}'\n";
    }

    // Purge ALL existing courses with id > 1 so reset starts completely fresh
    foreach ($courses as $c) {
        echo "Purging existing course ID {$c->id} ('{$c->shortname}')...\n";
        demo_cleanup_single_course((int)$c->id);
    }
}

/**
 * Thoroughly and safely delete a course, handling question bank corruption and DB constraints.
 */
function demo_cleanup_single_course(int $courseid): void {
    global $DB;

    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        echo "Course {$courseid} does not exist in DB.\n";
        return;
    }

    echo "Attempting to delete course {$courseid} ('{$course->shortname}')...\n";

    // 1. First, attempt standard Moodle delete_course()
    $deleted = false;
    try {
        $deleted = delete_course($courseid, false);
    } catch (\Throwable $e) {
        echo "Standard delete_course({$courseid}) threw: " . $e->getMessage() . "\n";
        if ($DB->is_transaction_started()) {
            try {
                $DB->force_transaction_rollback();
            } catch (\Throwable $te) {}
        }
    }

    if ($deleted && !$DB->record_exists('course', ['id' => $courseid])) {
        echo "Course {$courseid} deleted successfully via delete_course().\n";
        return;
    }

    echo "delete_course({$courseid}) did not remove the course from DB. Executing deep database purge...\n";

    // 2. Deep database purge:
    $ctx = \context_course::instance($courseid, IGNORE_MISSING);
    $ctxid = $ctx ? (int)$ctx->id : null;

    if ($ctxid) {
        // Find all question categories in this course context
        try {
            $catids = $DB->get_fieldset_select('question_categories', 'id', 'contextid = ?', [$ctxid]);
            if (!empty($catids)) {
                list($incat, $catparams) = $DB->get_in_or_equal($catids);
                $qids = $DB->get_fieldset_select('question', 'id', "category $incat", $catparams);
                if (!empty($qids)) {
                    list($inq, $qparams) = $DB->get_in_or_equal($qids);

                    // Question hints and answers
                    if ($DB->get_manager()->table_exists('question_hints')) {
                        $DB->delete_records_select('question_hints', "questionid $inq", $qparams);
                    }
                    if ($DB->get_manager()->table_exists('question_answers')) {
                        $DB->delete_records_select('question_answers', "question $inq", $qparams);
                    }
                    if ($DB->get_manager()->table_exists('question_versions')) {
                        $DB->delete_records_select('question_versions', "questionid $inq", $qparams);
                    }
                    $DB->delete_records_select('question', "id $inq", $qparams);
                }
                if ($DB->get_manager()->table_exists('question_references')) {
                    $DB->delete_records_select('question_references', 'usingcontextid = ?', [$ctxid]);
                }
                if ($DB->get_manager()->table_exists('question_set_references')) {
                    $DB->delete_records_select('question_set_references', 'usingcontextid = ?', [$ctxid]);
                }
                $DB->delete_records_select('question_categories', "id $incat", $catparams);
            }
        } catch (\Throwable $qe) {
            echo "Question pre-clean note: " . $qe->getMessage() . "\n";
        }

        // Try delete_course again after cleaning questions
        try {
            $deleted2 = delete_course($courseid, false);
            if ($deleted2 && !$DB->record_exists('course', ['id' => $courseid])) {
                echo "Course {$courseid} deleted on second attempt via delete_course().\n";
                return;
            }
        } catch (\Throwable $e2) {
            if ($DB->is_transaction_started()) {
                try {
                    $DB->force_transaction_rollback();
                } catch (\Throwable $te) {}
            }
        }
    }

    // 3. Fallback: Direct DB deletion of all course tables and context
    $cleanup_sqls = [
        "DELETE FROM {course_modules_completion} WHERE coursemoduleid IN (SELECT id FROM {course_modules} WHERE course = ?)",
        "DELETE FROM {course_modules} WHERE course = ?",
        "DELETE FROM {course_sections} WHERE course = ?",
        "DELETE FROM {course_format_options} WHERE courseid = ?",
        "DELETE FROM {user_enrolments} WHERE enrolid IN (SELECT id FROM {enrol} WHERE courseid = ?)",
        "DELETE FROM {enrol} WHERE courseid = ?",
        "DELETE FROM {grade_grades} WHERE itemid IN (SELECT id FROM {grade_items} WHERE courseid = ?)",
        "DELETE FROM {grade_items} WHERE courseid = ?",
        "DELETE FROM {event} WHERE courseid = ?",
        "DELETE FROM {logstore_standard_log} WHERE courseid = ?",
    ];

    foreach ($cleanup_sqls as $sql) {
        try {
            $DB->execute($sql, [$courseid]);
        } catch (\Throwable $ex) {}
    }

    // Clean contexts and files
    if ($ctxid) {
        try {
            $fs = get_file_storage();
            $fs->delete_area_files($ctxid);
        } catch (\Throwable $ex) {}

        try {
            $child_ctxs = $DB->get_fieldset_select('context', 'id', "path LIKE ?", ["%/{$ctxid}/%"]);
            if (!empty($child_ctxs)) {
                list($inctx, $ctxparams) = $DB->get_in_or_equal($child_ctxs);
                $DB->delete_records_select('role_assignments', "contextid $inctx", $ctxparams);
                $DB->delete_records_select('context', "id $inctx", $ctxparams);
            }
            $DB->delete_records('role_assignments', ['contextid' => $ctxid]);
            $DB->delete_records('context', ['id' => $ctxid]);
        } catch (\Throwable $ex) {
            echo "Context purge note: " . $ex->getMessage() . "\n";
        }
    }

    // Delete course record itself
    try {
        $DB->delete_records('course', ['id' => $courseid]);
        echo "Course {$courseid} record successfully deleted from {course}!\n";
    } catch (\Throwable $ex) {
        echo "Error deleting {course} record: " . $ex->getMessage() . "\n";
    }

    // Clean category and course caches
    try {
        \core_course_category::cleancache();
    } catch (\Throwable $ex) {}
}

/**
 * Restore a demo course from its MBZ archive.
 */
function demo_restore_course(string $mbzfile, string $shortname, string $fullname): ?int {
    global $DB;
    $mbz = __DIR__ . '/' . $mbzfile;
    if (!is_readable($mbz)) {
        echo "warning: missing {$mbz}, skipping {$shortname}\n";
        return null;
    }
    $folder = 'algdemo' . preg_replace('/[^a-zA-Z0-9]/', '', $shortname) . time();
    $path = make_backup_temp_directory($folder);
    get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($mbz, $path);
    $admin = get_admin();
    $existing = $DB->get_records('course', ['shortname' => $shortname]);
    foreach ($existing as $course) {
        demo_cleanup_single_course((int)$course->id);
    }
    $category = $DB->get_field('course_categories', 'id', [], IGNORE_MULTIPLE);
    $courseid = restore_dbops::create_new_course($fullname, $shortname, $category);
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $rc = new restore_controller(
        $folder,
        $course->id,
        backup::INTERACTIVE_NO,
        backup::MODE_GENERAL,
        $admin->id,
        backup::TARGET_NEW_COURSE
    );
    $rc->execute_precheck();
    $rc->execute_plan();
    $rc->destroy();
    $DB->set_field('course', 'shortname', $shortname, ['id' => $course->id]);
    $DB->set_field('course', 'fullname', $fullname, ['id' => $course->id]);
    $DB->set_field('course', 'visible', 1, ['id' => $course->id]);
    demo_guest_access($course->id);
    demo_teacher_limits($course->id);
    rebuild_course_cache($course->id, true);
    echo "course {$shortname} (id {$course->id}) restored\n";
    return (int) $course->id;
}

/**
 * Guests may look at the course (run the diagrams) without an account.
 */
function demo_guest_access(int $courseid): void {
    global $DB;
    $guest = enrol_get_plugin('guest');
    $course = get_course($courseid);
    $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'guest']);
    if (!$instance) {
        $guest->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
    } else if ($instance->status != ENROL_INSTANCE_ENABLED) {
        $guest->update_status($instance, ENROL_INSTANCE_ENABLED);
    }
}

/**
 * The demo teacher edits activities and questions, but not the course itself, its participants or its backups.
 */
function demo_teacher_limits(int $courseid): void {
    global $DB;
    $role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    foreach (['moodle/course:update', 'moodle/course:enrolconfig', 'moodle/course:enrolreview', 'moodle/role:assign',
            'enrol/manual:enrol', 'enrol/manual:manage', 'enrol/guest:config', 'moodle/backup:backupcourse',
            'moodle/restore:restorecourse', 'moodle/course:reset', 'moodle/course:delete'] as $cap) {
        if (get_capability_info($cap)) {
            assign_capability($cap, CAP_PROHIBIT, $role->id, $context->id, true);
        }
    }
    $context->mark_dirty();
}

/**
 * Welcome banner in section 0 of the demo course with 4-step guided tour, guide button, and language toggle.
 */
function demo_section_banner(int $courseid, string $lang, int $other_courseid): void {
    global $CFG, $DB;
    $other_url = "{$CFG->wwwroot}/course/view.php?id={$other_courseid}";

    // Locate the Guide page in this course
    $guide_btn = '';
    $guide_page = $DB->get_record_select('page', "course = ? AND (name LIKE ? OR name LIKE ?)", [$courseid, '%Guide%', '%Vodič%']);
    if ($guide_page) {
        $guide_cm = get_coursemodule_from_instance('page', $guide_page->id, $courseid);
        if ($guide_cm) {
            $guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$guide_cm->id}";
            $btn_text = ($lang === 'en') ? '📖 User &amp; Teacher Guide →' : '📖 Vodič za algoritme →';
            $guide_btn = '<a href="' . $guide_url . '" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold shadow-sm" style="backdrop-filter: blur(4px);">' . $btn_text . '</a>';
        }
    }

    if ($lang === 'en') {
        $banner = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 60%, #4f46e5 100%); color: #ffffff;">'
            . '<div class="card-body p-4">'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">'
            . '<div class="d-flex flex-wrap align-items-center gap-2">'
            . '<span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(4px);">✨ Interactive Flowcharts · English</span>'
            . $guide_btn
            . '</div>'
            . '<a href="' . $other_url . '" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold" style="backdrop-filter: blur(4px);">🇷🇸 Prebaci na srpski kurs →</a>'
            . '</div>'
            . '<h2 class="fw-bold text-white mb-2">Welcome to Algorithm Plugins for Moodle!</h2>'
            . '<p class="mb-3" style="color: rgba(255,255,255,0.9) !important; font-size: 1.05rem;">Draw algorithms in TinyMCE, step through blocks, and grade with test cases. Follow this 4-step tour to experience the plugins:</p>'
            . '<div class="row g-2 mb-3">'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-warning small mb-1">1. RUN &amp; STEP</div><div class="small opacity-90">Click Step to watch blocks highlight &amp; variables change</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-info small mb-1">2. SOLVE EXERCISES</div><div class="small opacity-90">Click Edit to add missing loops/functions &amp; Test</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-success small mb-1">3. 2D MATRICES</div><div class="small opacity-90">Explore multiplication table &amp; matrix functions</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-light small mb-1">4. QUIZZES &amp; FORUM</div><div class="small opacity-90">Take auto-graded quiz &amp; share diagrams in forum</div></div></div>'
            . '</div>'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small opacity-90">'
            . '<div><strong>Demo Accounts:</strong> <code>student</code> or <code>teacher</code> (password: <code>Demo-2026</code>) · or explore as Guest</div>'
            . '<div>Resets nightly at 03:30</div>'
            . '</div></div></div>';
    } else {
        $banner = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 60%, #4f46e5 100%); color: #ffffff;">'
            . '<div class="card-body p-4">'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">'
            . '<div class="d-flex flex-wrap align-items-center gap-2">'
            . '<span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(4px);">✨ Dijagrami toka · Srpski</span>'
            . $guide_btn
            . '</div>'
            . '<a href="' . $other_url . '" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-bold" style="backdrop-filter: blur(4px);">🌐 Switch to English Course →</a>'
            . '</div>'
            . '<h2 class="fw-bold text-white mb-2">Dobrodošli u pluginove za algoritme u Moodle-u!</h2>'
            . '<p class="mb-3" style="color: rgba(255,255,255,0.9) !important; font-size: 1.05rem;">Crtajte algoritme u TinyMCE, koračajte kroz blokove i automatski ocenjujte zadatke. Isprobajte sve u 4 koraka:</p>'
            . '<div class="row g-2 mb-3">'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-warning small mb-1">1. RUN I STEP</div><div class="small opacity-90">Kliknite Step i posmatrajte koračanje i tabelu promenljivih</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-info small mb-1">2. REŠAVANJE VEŽBI</div><div class="small opacity-90">Kliknite Edit, dopunite petlju ili funkciju i testirajte</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-success small mb-1">3. 2D MATRICE</div><div class="small opacity-90">Isprobajte tablicu množenja i funkcije sa matricom</div></div></div>'
            . '<div class="col-md-3 col-6"><div class="p-2 px-3 rounded-3 bg-black bg-opacity-30 h-100"><div class="fw-bold text-light small mb-1">4. KVIZ I FORUM</div><div class="small opacity-90">Uradite automatski kviz i objavite dijagram na forumu</div></div></div>'
            . '</div>'
            . '<div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small opacity-90">'
            . '<div><strong>Demo nalozi:</strong> <code>student</code> ili <code>teacher</code> (lozinka: <code>Demo-2026</code>) · ili pristupite kao Gost</div>'
            . '<div>Resetuje se svake noći u 03:30</div>'
            . '</div></div></div>';
    }

    $DB->set_field('course_sections', 'summary', $banner, ['course' => $courseid, 'section' => 0]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => $courseid, 'section' => 0]);
}

/**
 * Fallback banner when only one course is available.
 */
function demo_section_banner_single(int $courseid): void {
    global $DB;
    $banner = '<div class="card border-0 shadow-sm rounded-4 mb-4 p-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 100%);">'
        . '<h2 class="fw-bold mb-2">Welcome to Algorithm Plugins for Moodle!</h2>'
        . '<p class="mb-3 text-white-50">Run algorithms step-by-step, inspect variables, switch between 7 programming languages, or test automatic grading in quizzes.</p>'
        . '</div>';
    $DB->set_field('course_sections', 'summary', $banner, ['course' => $courseid, 'section' => 0]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => $courseid, 'section' => 0]);
}

/**
 * The shared demo accounts enrolled in the demo course.
 */
function demo_users(int $courseid): void {
    global $CFG, $DB;
    $manual = enrol_get_plugin('manual');
    $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
    if (!$instance) {
        $manual->add_instance(get_course($courseid));
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], '*', MUST_EXIST);
    }
    foreach (DEMO_USERS as $username => $u) {
        $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
        if (!$user) {
            $id = user_create_user((object) [
                'username' => $username,
                'password' => DEMO_PASSWORD,
                'auth' => 'manual',
                'confirmed' => 1,
                'mnethostid' => $CFG->mnet_localhost_id,
                'firstname' => $u['firstname'],
                'lastname' => $u['lastname'],
                'email' => "{$username}@algorithm.tsp.edu.rs",
                'lang' => 'en',
            ], true, false);
            $user = $DB->get_record('user', ['id' => $id], '*', MUST_EXIST);
        } else {
            update_internal_user_password($user, DEMO_PASSWORD);
        }
        $roleid = $DB->get_field('role', 'id', ['shortname' => $u['role']], MUST_EXIST);
        $manual->enrol_user($instance, $user->id, $roleid);
        echo "user {$username} enrolled in course {$courseid} as {$u['role']}\n";
    }
}

/**
 * Front page with dual-language course cards, quick demo access, and plugin cards.
 */
function demo_frontpage(int $en_id, int $sr_id): void {
    global $CFG, $DB;
    $en_url = "{$CFG->wwwroot}/course/view.php?id={$en_id}";
    $sr_url = "{$CFG->wwwroot}/course/view.php?id={$sr_id}";

    // Locate guides
    $en_guide_url = "{$CFG->wwwroot}/course/view.php?id={$en_id}";
    $sr_guide_url = "{$CFG->wwwroot}/course/view.php?id={$sr_id}";
    $g_en = $DB->get_record_select('page', "course = ? AND name LIKE ?", [$en_id, '%Guide%']);
    if ($g_en) {
        $cm = get_coursemodule_from_instance('page', $g_en->id, $en_id);
        if ($cm) {
            $en_guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$cm->id}";
        }
    }
    $g_sr = $DB->get_record_select('page', "course = ? AND name LIKE ?", [$sr_id, '%Vodič%']);
    if ($g_sr) {
        $cm = get_coursemodule_from_instance('page', $g_sr->id, $sr_id);
        if ($cm) {
            $sr_guide_url = "{$CFG->wwwroot}/mod/page/view.php?id={$cm->id}";
        }
    }

    $summary = '<p>This is the demo site of the <strong>algorithm plugins for Moodle</strong>: '
        . '<a href="https://moodle.org/plugins/tiny_algorithm">tiny_algorithm</a> (flowchart editor), '
        . '<a href="https://moodle.org/plugins/filter_algorithm">filter_algorithm</a> (run and step for readers) and '
        . '<a href="https://moodle.org/plugins/qtype_algorithm">qtype_algorithm</a> (automatically graded questions).</p>'
        . '<p>Open the demo courses as a guest, or log in with one of these accounts (password <strong>' . DEMO_PASSWORD . '</strong>):</p><ul>'
        . '<li><strong>teacher</strong>: edit the pages and the quiz questions, insert flowcharts</li>'
        . '<li><strong>student</strong>: solve the exercises, take the quiz, post in the forum</li></ul>'
        . '<p>Courses available: <a href="' . $en_url . '"><strong>Algorithms with flowcharts: demo (English)</strong></a> · <a href="' . $sr_url . '"><strong>Algoritmi sa dijagramima toka: demo (srpski)</strong></a> · <a href="' . $en_guide_url . '"><strong>📖 User &amp; Teacher Guide</strong></a></p>'
        . '<p>Everything you change is reset every night. Serbian (Latin and Cyrillic) is in the language menu.</p>';
    $DB->update_record('course', (object) [
        'id' => SITEID,
        'fullname' => 'Algorithm plugins for Moodle: demo',
        'shortname' => 'Algorithm demo',
        'summary' => $summary,
        'summaryformat' => FORMAT_HTML,
    ]);

    $hero = '<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 50%, #4f46e5 100%); color: #ffffff;">'
        . '<div class="card-body p-4 p-md-5">'
        . '<div class="row align-items-center">'
        . '<div class="col-lg-8">'
        . '<div class="d-flex flex-wrap gap-2 mb-3">'
        . '<span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal" style="backdrop-filter: blur(4px);">✨ Official Demo for Moodle Algorithm Plugins</span>'
        . '<span class="badge bg-info bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal">🧱 TinyMCE Editor</span>'
        . '<span class="badge bg-warning bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal">▶️ Step Execution</span>'
        . '<span class="badge bg-success bg-opacity-25 text-white px-3 py-2 rounded-pill fw-normal">🌐 7 Programming Languages</span>'
        . '<a href="' . $en_guide_url . '" class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold text-decoration-none shadow-sm">📖 User &amp; Teacher Guide →</a>'
        . '</div>'
        . '<h1 class="display-6 fw-bold text-white mb-3">Interactive Flowcharts &amp; Algorithms in Moodle</h1>'
        . '<p class="lead mb-4" style="color: rgba(255,255,255,0.92) !important; font-size: 1.15rem;">Draw algorithms directly in TinyMCE, run them step-by-step with live variable watches, convert to 7 programming languages, and grade submissions automatically with test cases.</p>'
        . '<div class="d-flex flex-wrap gap-3 mb-2">'
        . '<a href="' . $en_url . '" class="btn btn-light btn-lg px-4 fw-bold shadow-sm" style="border-radius: 12px; color: #312e81;">🇬🇧 Open English Demo</a>'
        . '<a href="' . $sr_url . '" class="btn btn-outline-light btn-lg px-4 fw-bold shadow-sm" style="border-radius: 12px; border-width: 2px;">🇷🇸 Otvori kurs na srpskom</a>'
        . '<a href="' . $en_guide_url . '" class="btn btn-warning text-dark btn-lg px-4 fw-bold shadow-sm" style="border-radius: 12px;">📖 User &amp; Teacher Guide</a>'
        . '</div>'
        . '</div>'
        . '<div class="col-lg-4 text-center d-none d-lg-block">'
        . '<div class="p-3 bg-white bg-opacity-10 rounded-4 shadow-sm" style="backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2);">'
        . '<div class="fw-bold fs-5 text-white mb-2">⚡ Quick Test Accounts</div>'
        . '<div class="text-start bg-dark bg-opacity-50 p-3 rounded-3 font-monospace small text-white mb-2">'
        . '<div><strong>Teacher:</strong> teacher</div><div><strong>Student:</strong> student</div><div><strong>Password:</strong> ' . DEMO_PASSWORD . '</div>'
        . '</div>'
        . '<div class="small text-white-50">Or explore as <strong>Guest</strong>! Courses reset nightly at 03:30.</div>'
        . '</div></div></div></div></div>'
        // Dual-Language Cards
        . '<div class="row g-3 mb-4">'
        . '<div class="col-md-6">'
        . '<div class="card h-100 border-0 shadow-sm p-4 demo-course-card" style="background:#ffffff; border-left: 5px solid #4f46e5 !important;">'
        . '<div class="d-flex justify-content-between align-items-start mb-2">'
        . '<h4 class="fw-bold mb-0 text-primary">🇬🇧 Algorithms: Demo (English)</h4>'
        . '<span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">ALGORITHM-DEMO</span>'
        . '</div>'
        . '<p class="text-muted mb-3">Run flowcharts step-by-step, inspect variables in real-time, translate to 7 languages (Python, C, C++, Java, C#, JS, Pascal), and solve exercises with test cases.</p>'
        . '<a href="' . $en_url . '" class="btn btn-primary fw-bold px-4 rounded-3 align-self-start">🚀 Enter English Course →</a>'
        . '</div></div>'
        . '<div class="col-md-6">'
        . '<div class="card h-100 border-0 shadow-sm p-4 demo-course-card" style="background:#ffffff; border-left: 5px solid #059669 !important;">'
        . '<div class="d-flex justify-content-between align-items-start mb-2">'
        . '<h4 class="fw-bold mb-0 text-success">🇷🇸 Algoritmi: Demo (srpski)</h4>'
        . '<span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill">ALGORITHM-DEMO-SR</span>'
        . '</div>'
        . '<p class="text-muted mb-3">Crtanje dijagrama toka u TinyMCE, koračanje, praćenje vrednosti promenljivih, dvodimenzionalne matrice, funkcije i automatski ocenjivani kvizovi.</p>'
        . '<a href="' . $sr_url . '" class="btn btn-success fw-bold px-4 rounded-3 align-self-start">🇷🇸 Otvori kurs na srpskom →</a>'
        . '</div></div>'
        . '</div>'
        // Test Accounts Strip
        . '<div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-light">'
        . '<div class="row g-2 align-items-center text-center text-md-start">'
        . '<div class="col-md-3 fw-bold text-dark"><span class="fs-5 me-2">⚡</span>Instant Demo Access:</div>'
        . '<div class="col-md-3"><div class="p-2 px-3 bg-white rounded-3 shadow-sm border small"><strong>👤 Guest:</strong> Run &amp; step through all diagrams</div></div>'
        . '<div class="col-md-3"><div class="p-2 px-3 bg-white rounded-3 shadow-sm border small"><strong>🎓 Student:</strong> <code>student</code> / <code>Demo-2026</code></div></div>'
        . '<div class="col-md-3"><div class="p-2 px-3 rounded-3 bg-white rounded-3 shadow-sm border small"><strong>🧑‍🏫 Teacher:</strong> <code>teacher</code> / <code>Demo-2026</code></div></div>'
        . '</div></div>'
        // 3 Plugin Cards
        . '<div class="row g-3 mb-4">'
        . '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-3 p-3"><div class="d-flex align-items-center mb-2"><span class="fs-4 me-2">🧱</span><h5 class="card-title mb-0 fw-bold"><a href="https://moodle.org/plugins/tiny_algorithm" target="_blank" class="text-decoration-none">tiny_algorithm</a></h5></div><p class="card-text text-muted small mb-0">Visual flowchart editor for TinyMCE with drag &amp; drop, matrices, undo/redo, and code generator for Python, C, C++, Java, C#, JS &amp; Pascal.</p></div></div>'
        . '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-3 p-3"><div class="d-flex align-items-center mb-2"><span class="fs-4 me-2">▶️</span><h5 class="card-title mb-0 fw-bold"><a href="https://moodle.org/plugins/filter_algorithm" target="_blank" class="text-decoration-none">filter_algorithm</a></h5></div><p class="card-text text-muted small mb-0">Text filter that turns static SVG diagrams into an interactive step-by-step viewer with variable watch and student editing &amp; testing.</p></div></div>'
        . '<div class="col-md-4"><div class="card h-100 border-0 shadow-sm rounded-3 p-3"><div class="d-flex align-items-center mb-2"><span class="fs-4 me-2">📋</span><h5 class="card-title mb-0 fw-bold"><a href="https://moodle.org/plugins/qtype_algorithm" target="_blank" class="text-decoration-none">qtype_algorithm</a></h5></div><p class="card-text text-muted small mb-0">Moodle question type for quizzes: students draw algorithms, run them, and are graded automatically against test cases.</p></div></div>'
        . '</div>';

    $DB->set_field('course_sections', 'summary', $hero, ['course' => SITEID, 'section' => 1]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => SITEID, 'section' => 1]);
    set_config('numsections', 1);
    set_config('frontpage', FRONTPAGEALLCOURSELIST);
    set_config('frontpageloggedin', FRONTPAGEALLCOURSELIST);
    echo "front page done\n";
}

/**
 * Front page with a single course fallback.
 */
function demo_frontpage_single(int $courseid): void {
    global $CFG, $DB;
    $url = "{$CFG->wwwroot}/course/view.php?id={$courseid}";
    $hero = '<div class="card border-0 shadow-sm rounded-4 mb-4 p-5 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #312e81 100%);">'
        . '<h1 class="display-6 fw-bold mb-3">Algorithm Plugins for Moodle Demo</h1>'
        . '<a href="' . $url . '" class="btn btn-light btn-lg px-4 fw-bold">Open Course</a>'
        . '</div>';
    $DB->set_field('course_sections', 'summary', $hero, ['course' => SITEID, 'section' => 1]);
    $DB->set_field('course_sections', 'summaryformat', FORMAT_HTML, ['course' => SITEID, 'section' => 1]);
}
