<?php

namespace Composer\Installers;

use Composer\IO\IOInterface;
use Composer\Composer;
use Composer\Package\PackageInterface;

class MoodleInstaller extends BaseInstaller
{
    private ?PackageInterface $moodlePackage = null;

    protected $locations = [
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

    /**
     * Initializes base installer.
     */
    public function __construct(PackageInterface $package = null, Composer $composer = null, IOInterface $io = null)
    {
        parent::__construct($package, $composer, $io);

        // Find the Moodle Package.
        $this->moodlePackage = $composer->getRepositoryManager()->findPackage('moodle/moodle', '*');
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
}
