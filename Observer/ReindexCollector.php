<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Indexer\Observer;

use Weline\Framework\App\Env;
use Weline\Framework\Database\AbstractModel;
use Weline\Framework\Event\Event;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Module\Config\ModuleFileReader;
use Weline\Indexer\Model\Indexer;

class ReindexCollector implements \Weline\Framework\Event\ObserverInterface
{
    private ModuleFileReader $moduleFileReader;
    private Indexer $indexer;

    /**
     *
     * @param ModuleFileReader $moduleFileReader
     * @param Indexer          $indexer
     */
    public function __construct(
        ModuleFileReader $moduleFileReader,
        Indexer          $indexer
    )
    {
        $this->moduleFileReader = $moduleFileReader;
        $this->indexer          = $indexer;
    }

    /**
     * @inheritDoc
     */
    public function execute(Event $event)
    {
        $modules = Env::getInstance()->getModuleList();
        foreach ($modules as $module) {
            $models = $this->moduleFileReader->read($module['name'], 'Model');
            foreach ($models as $model_files) {
                /**@var \Weline\Framework\System\File\Data\File $model_file */
                foreach ($model_files as $model_file) {
                    $model = $model_file->getNamespace() . '\\' . $model_file->getFilename();
                    if (class_exists($model)) {
                        $model = ObjectManager::getInstance($model);
                        if ($model instanceof AbstractModel && $indexer = $model::indexer) {
                            # 检测是否有indexer
                            $hasIndexer = $this->indexer->where([['name', $indexer], ['model', $model::class]])->find()->fetch();
                            if (!$hasIndexer->getId()) {
                                # 如果没有indexer，则创建
                                $this->indexer->setName($indexer);
                                $this->indexer->setModule($module['name']);
                                $this->indexer->setModel($model::class);
                                $this->indexer->setTable($model->getTable());
                                $this->indexer->save();
                            }
                        }
                    }
                }
            }
        }
    }
}
