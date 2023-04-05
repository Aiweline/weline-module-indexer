<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Indexer\Observer;

use Weline\Framework\Event\Event;
use Weline\Framework\Manager\ObjectManager;
use Weline\Framework\Output\Cli\Printing;
use Weline\Indexer\Model\Indexer;

class Reindex implements \Weline\Framework\Event\ObserverInterface
{
    private \Weline\Indexer\Model\Indexer $indexer;
    private Printing $printing;

    public function __construct(
        \Weline\Indexer\Model\Indexer $indexer,
        Printing                      $printing
    )
    {
        $this->indexer  = $indexer;
        $this->printing = $printing;
    }

    /**
     * @inheritDoc
     */
    public function execute(Event $event)
    {
        $args = $event->getData('args');
        # 检测是否自定义索引重建
        array_shift($args);
        $args_indexers = $args;
        if ($args_indexers) {
            # 查找自定义索引是否在数据库中
            foreach ($args_indexers as $args_indexer) {
                $indexers = $this->indexer->where('name', $args_indexer)->select()->fetch();
                if (!$indexers) {
                    $this->printing->error('索引器 ' . $args_indexer . ' 找不到');
                    continue;
                }
                foreach ($indexers as $indexer) {
                    if (class_exists($indexer->getModel())) {
                        $model = ObjectManager::getInstance($indexer->getModel());
                        $this->printing->note("开始重建索引：{$indexer['name']}");
                        $model->reindex($model->getTable());
                        $this->printing->success("索引重建完成：{$indexer['name']}");
                    } else {
                        $this->printing->error('索引模型不存在');
                        return;
                    }
                }
            }
        } else {
            # 检索Model模型
            $indexers = $this->indexer->select()->fetch();
            /**@var Indexer $indexer */
            foreach ($indexers as $indexer) {
                $this->printing->note("开始重建索引：{$indexer['name']}");
                if (class_exists($indexer->getModel())) {
                    $model = ObjectManager::getInstance($indexer->getModel());
                    $model->reindex($model->getTable());
                    $this->printing->success("索引重建完成：{$indexer['name']}");
                } else {
                    $this->printing->error('索引模型不存在');
                    return;
                }
            }
        }

        # 所有索引重建完成
        $this->printing->note('所有索引重建完成');
    }
}
