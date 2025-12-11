<?php

namespace Composer\Installers;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Package\PackageInterface;

class MoodleInstaller extends BaseInstaller
{
    private ?PackageInterface $moodlePackage = null;

    /**
     * Initializes base installer.
     */
    public function __construct(PackageInterface $package = null, Composer $composer = null, IOInterface $io = null)
    {
        xdebug_break();
        parent::__construct($package, $composer, $io);

        // Find the Moodle Package.
        $this->moodlePackage = $composer->getRepositoryManager()->findPackage('moodle/moodle', '*');

        $this->_loadLegacyList();
        return;


        $configurations = [
            [
                'publicdir' => true,
                'project' => true,
            ],
            [
                'publicdir' => true,
                'project' => false,
            ],
            [
                'publicdir' => false,
                'project' => false,
            ],
        ];

        $path = 'lib/components.json';
        foreach ($configurations as $config) {
            $basePath = getcwd();

            if ($config['project']) {
                $basePath .= '/moodle';
            }

            $componentFile = $basePath . '/' . $path;

            if (file_exists($componentFile)) {
                if ($config['publicdir'] && !file_exists($basePath . '/public/')) {
                    continue;
                }
                $this->_loadDynamicLocations(
                    $componentFile,
                    $config['project'],
                    $config['publicdir']
                );
                return;
            }
        }

        // Not found - fall back to the legacy list.
        $this->_loadLegacyList();
        return;
    }

    /**
     * Load dynamic plugin locations from Moodle's components.json file.
     *
     * @param string $componentFile Path to the components.json file.
     * @param array<string, bool> $config Configuration options.
     * @return void
     */
    private function _loadDynamicLocations(
        string $componentFile,
        bool $isProject = false,
        bool $haspublicdir = false
    ): void {
        $locations = [];

        // Moodle maintains a list of plugin types and their location in a
        // components.json file.
        // This is the authoritative list and should be used wherever possible.
        $components = json_decode(file_get_contents($componentFile), true);
        $locations = [];

        $basePath = getcwd() . '/';
        if ($isProject) {
            $basePath .= 'moodle/';
            $locations['core'] = $basePath;
        }

        $locations += array_map(
            function (string $path) use ($basePath) {
                return $basePath . $path . '/{$name}';
            },
            $components['plugintypes']
        );

        // This could be a subplugin.
        // Plugins can define subplugins in a db/subplugins.json file.
        foreach (array_values($components['plugintypes']) as $path) {
            $path = $basePath . $path;
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

    public function inflectPackageVars(array $vars): array
    {
        if (substr($vars['type'], 0, 7) !== 'moodle-') {
            return $vars;
        }

        $moodleExtra = $this->moodlePackage ? $this->moodlePackage->getExtra() : [];

        $vars['public'] = !empty($moodleExtra['moodle-public']) ? 'moodle/public/' : '';

        $matches = [];
        preg_match('/^moodle-(?<type>([^_]*))_(?<name>(.*))$/', $vars['name'], $matches);

        if ($matches) {
            $vars['name'] = $matches['name'];
        }

        return $vars;
    }


    private function _loadLegacyList(): void {
        $this->locations = [
            'core'               => 'moodle/',
            'mod'                => '{$public}mod/{$name}/',
            'admin_report'       => '{$public}admin/report/{$name}/',
            'atto'               => '{$public}lib/editor/atto/plugins/{$name}/',
            'tool'               => '{$public}admin/tool/{$name}/',
            'assignment'         => '{$public}mod/assignment/type/{$name}/',
            'assignsubmission'   => '{$public}mod/assign/submission/{$name}/',
            'assignfeedback'     => '{$public}mod/assign/feedback/{$name}/',
            'antivirus'          => '{$public}lib/antivirus/{$name}/',
            'auth'               => '{$public}auth/{$name}/',
            'availability'       => '{$public}availability/condition/{$name}/',
            'block'              => '{$public}blocks/{$name}/',
            'booktool'           => '{$public}mod/book/tool/{$name}/',
            'cachestore'         => '{$public}cache/stores/{$name}/',
            'cachelock'          => '{$public}cache/locks/{$name}/',
            'calendartype'       => '{$public}calendar/type/{$name}/',
            'communication'      => '{$public}communication/provider/{$name}/',
            'customfield'        => '{$public}customfield/field/{$name}/',
            'fileconverter'      => '{$public}files/converter/{$name}/',
            'format'             => '{$public}course/format/{$name}/',
            'coursereport'       => '{$public}course/report/{$name}/',
            'contenttype'        => '{$public}contentbank/contenttype/{$name}/',
            'customcertelement'  => '{$public}mod/customcert/element/{$name}/',
            'datafield'          => '{$public}mod/data/field/{$name}/',
            'dataformat'         => '{$public}dataformat/{$name}/',
            'datapreset'         => '{$public}mod/data/preset/{$name}/',
            'editor'             => '{$public}lib/editor/{$name}/',
            'enrol'              => '{$public}enrol/{$name}/',
            'filter'             => '{$public}filter/{$name}/',
            'forumreport'        => '{$public}mod/forum/report/{$name}/',
            'gradeexport'        => '{$public}grade/export/{$name}/',
            'gradeimport'        => '{$public}grade/import/{$name}/',
            'gradereport'        => '{$public}grade/report/{$name}/',
            'gradingform'        => '{$public}grade/grading/form/{$name}/',
            'h5plib'             => '{$public}h5p/h5plib/{$name}/',
            'local'              => '{$public}local/{$name}/',
            'logstore'           => '{$public}admin/tool/log/store/{$name}/',
            'ltisource'          => '{$public}mod/lti/source/{$name}/',
            'ltiservice'         => '{$public}mod/lti/service/{$name}/',
            'media'              => '{$public}media/player/{$name}/',
            'message'            => '{$public}message/output/{$name}/',
            'mlbackend'          => '{$public}lib/mlbackend/{$name}/',
            'mnetservice'        => '{$public}mnet/service/{$name}/',
            'paygw'              => '{$public}payment/gateway/{$name}/',
            'plagiarism'         => '{$public}plagiarism/{$name}/',
            'portfolio'          => '{$public}portfolio/{$name}/',
            'qbank'              => '{$public}question/bank/{$name}/',
            'qbehaviour'         => '{$public}question/behaviour/{$name}/',
            'qformat'            => '{$public}question/format/{$name}/',
            'qtype'              => '{$public}question/type/{$name}/',
            'quizaccess'         => '{$public}mod/quiz/accessrule/{$name}/',
            'quiz'               => '{$public}mod/quiz/report/{$name}/',
            'report'             => '{$public}report/{$name}/',
            'repository'         => '{$public}repository/{$name}/',
            'scormreport'        => '{$public}mod/scorm/report/{$name}/',
            'search'             => '{$public}search/engine/{$name}/',
            'theme'              => '{$public}theme/{$name}/',
            'tiny'               => '{$public}lib/editor/tiny/plugins/{$name}/',
            'tinymce'            => '{$public}lib/editor/tinymce/plugins/{$name}/',
            'profilefield'       => '{$public}user/profile/field/{$name}/',
            'webservice'         => '{$public}webservice/{$name}/',
            'workshopallocation' => '{$public}mod/workshop/allocation/{$name}/',
            'workshopeval'       => '{$public}mod/workshop/eval/{$name}/',
            'workshopform'       => '{$public}mod/workshop/form/{$name}/'
        ];
    }
}
