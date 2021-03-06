<?php

if (!in_array('sfDoctrinePlugin', sfProjectConfiguration::getActive()->getPlugins()))
{
  return false;
}

require_once dirname(__FILE__).'/sfTaskExtraDoctrineBaseTask.class.php';

/**
 * Builds form classes in the application lib directory.
 * 
 * @package    sfTaskExtraPlugin
 * @subpackage task
 * @author     Kris Wallsmith <kris.wallsmith@symfony-project.com>
 * @version    SVN: $Id: sfDoctrineBuildAppFormsTask.class.php 28187 2010-02-22 16:53:57Z Kris.Wallsmith $
 */
class sfDoctrineBuildAppFormsTask extends sfTaskExtraDoctrineBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addArguments(array(
      new sfCommandArgument('application', sfCommandArgument::REQUIRED, 'The application name'),
    ));

    $this->namespace = 'doctrine';
    $this->name = 'build-app-forms';

    $this->briefDescription = 'Builds form classes in the application lib directory';

    $this->detailedDescription = <<<EOF
The [doctrine:build-app-forms|INFO] task generates extensions of model forms in
an application's [lib/form/doctrine/|COMMENT] directory:

    [php symfony doctrine:build-app-forms frontend|INFO]

The generated stub classes will be named in the format [%app%%model%Form|COMMENT]
and will extend the stub form classes generated by the [doctrine:build-forms|COMMENT]
task. For example, [frontendArticleForm|COMMENT] may be generated, which would extend
[ArticleForm|COMMENT].

You can provide a custom skeleton for this task by creating the following
directory structure in your project:

    data/
      skeleton/
        doctrine_app_form/
          form.class.php
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);

    $this->checkAppExists($arguments['application']);

    // skeleton directory
    if (is_readable(sfConfig::get('sf_data_dir').'/skeleton/doctrine_app_form'))
    {
      $skeletonDir = sfConfig::get('sf_data_dir').'/skeleton/doctrine_app_form';
    }
    else
    {
      $skeletonDir = dirname(__FILE__).'/skeleton/app_form';
    }

    // target directory
    if (!file_exists($file = sfConfig::get('sf_app_lib_dir').'/form/doctrine'))
    {
      $this->getFilesystem()->mkdirs($file);
    }

    // constants
    $properties = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);
    $constants = array(
      'PROJECT'     => isset($properties['symfony']['name']) ? $properties['symfony']['name'] : 'symfony',
      'AUTHOR'      => isset($properties['symfony']['author']) ? $properties['symfony']['author'] : 'Your name here',
      'APPLICATION' => $arguments['application'],
    );

    foreach ($this->loadModels() as $model)
    {
      $file = sfConfig::get('sf_app_lib_dir').'/form/doctrine/'.$arguments['application'].$model.'Form.class.php';
      if (class_exists($model.'Form') && !file_exists($file))
      {
        $this->getFilesystem()->copy($skeletonDir.'/form.class.php', $file);
        $this->getFilesystem()->replaceTokens($file, '##', '##', $constants + array('MODEL' => $model));
      }
    }

    $this->reloadAutoload();
  }
}
