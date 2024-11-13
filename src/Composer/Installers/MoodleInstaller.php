<?php

namespace Composer\Installers;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Package\PackageInterface;

class MoodleInstaller extends BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array(
        'mod'                => 'mod/{$name}/',
        'admin_report'       => 'admin/report/{$name}/',
        'atto'               => 'lib/editor/atto/plugins/{$name}/',
        'tool'               => 'admin/tool/{$name}/',
        'assignment'         => 'mod/assignment/type/{$name}/',
        'assignsubmission'   => 'mod/assign/submission/{$name}/',
        'assignfeedback'     => 'mod/assign/feedback/{$name}/',
        'antivirus'          => 'lib/antivirus/{$name}/',
        'auth'               => 'auth/{$name}/',
        'availability'       => 'availability/condition/{$name}/',
        'block'              => 'blocks/{$name}/',
        'booktool'           => 'mod/book/tool/{$name}/',
        'cachestore'         => 'cache/stores/{$name}/',
        'cachelock'          => 'cache/locks/{$name}/',
        'calendartype'       => 'calendar/type/{$name}/',
        'communication'      => 'communication/provider/{$name}/',
        'customfield'        => 'customfield/field/{$name}/',
        'fileconverter'      => 'files/converter/{$name}/',
        'format'             => 'course/format/{$name}/',
        'coursereport'       => 'course/report/{$name}/',
        'contenttype'        => 'contentbank/contenttype/{$name}/',
        'customcertelement'  => 'mod/customcert/element/{$name}/',
        'datafield'          => 'mod/data/field/{$name}/',
        'dataformat'         => 'dataformat/{$name}/',
        'datapreset'         => 'mod/data/preset/{$name}/',
        'editor'             => 'lib/editor/{$name}/',
        'enrol'              => 'enrol/{$name}/',
        'filter'             => 'filter/{$name}/',
        'forumreport'        => 'mod/forum/report/{$name}/',
        'gradeexport'        => 'grade/export/{$name}/',
        'gradeimport'        => 'grade/import/{$name}/',
        'gradereport'        => 'grade/report/{$name}/',
        'gradingform'        => 'grade/grading/form/{$name}/',
        'h5plib'             => 'h5p/h5plib/{$name}/',
        'local'              => 'local/{$name}/',
        'logstore'           => 'admin/tool/log/store/{$name}/',
        'ltisource'          => 'mod/lti/source/{$name}/',
        'ltiservice'         => 'mod/lti/service/{$name}/',
        'media'              => 'media/player/{$name}/',
        'message'            => 'message/output/{$name}/',
        'mlbackend'          => 'lib/mlbackend/{$name}/',
        'mnetservice'        => 'mnet/service/{$name}/',
        'paygw'              => 'payment/gateway/{$name}/',
        'plagiarism'         => 'plagiarism/{$name}/',
        'portfolio'          => 'portfolio/{$name}/',
        'qbank'              => 'question/bank/{$name}/',
        'qbehaviour'         => 'question/behaviour/{$name}/',
        'qformat'            => 'question/format/{$name}/',
        'qtype'              => 'question/type/{$name}/',
        'quizaccess'         => 'mod/quiz/accessrule/{$name}/',
        'quiz'               => 'mod/quiz/report/{$name}/',
        'report'             => 'report/{$name}/',
        'repository'         => 'repository/{$name}/',
        'scormreport'        => 'mod/scorm/report/{$name}/',
        'search'             => 'search/engine/{$name}/',
        'theme'              => 'theme/{$name}/',
        'tiny'               => 'lib/editor/tiny/plugins/{$name}/',
        'tinymce'            => 'lib/editor/tinymce/plugins/{$name}/',
        'profilefield'       => 'user/profile/field/{$name}/',
        'webservice'         => 'webservice/{$name}/',
        'workshopallocation' => 'mod/workshop/allocation/{$name}/',
        'workshopeval'       => 'mod/workshop/eval/{$name}/',
        'workshopform'       => 'mod/workshop/form/{$name}/'
    );

    /**
     * Initializes base installer.
     */
    public function __construct(PackageInterface $package = null, Composer $composer = null, IOInterface $io = null)
    {
        parent::__construct($package, $composer, $io);

        $componentsfile = getcwd() . '/lib/components.json';
        if (!file_exists($componentsfile)) {
            // Must be a very old version of Moodle.
            // Fall back on the default install path list.
            return;
        }

        $locations = [];

        // Moodle maintains a list of plugin types and their location in a
        // components.json file.
        // This is the authoritative list and should be used wherever possible.
        $components = json_decode(file_get_contents($componentsfile), true);
        $locations = array_map(
            function(string $path) {
                return $path . '/{$name}';
            },
            $components['plugintypes']
        );

        // This could be a subplugin.
        // Plugins can define subplugins in a db/subplugins.json file.
        foreach (array_values($components['plugintypes']) as $path) {
            $iterator = new \DirectoryIterator($path);
            foreach ($iterator as $plugin) {
                $subpluginFile = $plugin->getPathname() . '/db/subplugins.json';
                if ($plugin->isDir() && is_file($subpluginFile)) {
                    $subplugins = json_decode(file_get_contents($subpluginFile), true);

                    if (array_key_exists('subplugintypes', $subplugins)) {
                        // In Moodle 5.0, subplugins are defined in a
                        // 'subplugintypes' array.
                        // This value is relative to the plugin directory.
                        $subpluginTypes = $subplugins['subplugintypes'];
                        foreach ($subpluginTypes as $pluginType => $subpluginPath) {
                            $locations[$pluginType] = $plugin->getPathname() . '/' . $subpluginPath . '/{$name}';
                        }
                    } else if (array_key_exists('plugintypes', $subplugins)) {
                        // Before Moodle 5.0, subplugins are defined
                        // in a 'plugintypes' array.
                        // This value is relative to the project root.
                        $subpluginTypes = $subplugins['plugintypes'];
                        foreach ($subpluginTypes as $pluginType => $subpluginPath) {
                            $locations[$pluginType] = $subpluginPath. '/{$name}';
                        }
                    }
                }
            }
        }

        $this->locations = $locations;
    }
}
