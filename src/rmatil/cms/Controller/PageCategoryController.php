<?php

namespace rmatil\cms\Controller;

use SlimController\SlimController;
use rmatil\cms\Constants\HttpStatusCodes;
use rmatil\cms\Entities\Article;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\DBALException;
use DateTime;

class PageCategoryController extends SlimController {

    private static $PAGE_FULL_QUALIFIED_CLASSNAME = 'rmatil\cms\Entities\PageCategory';

    public function getPageCategoriesAction() {
        $entityManager              = $this->app->entityManager;
        $pageCategoryRepository     = $entityManager->getRepository(self::$PAGE_FULL_QUALIFIED_CLASSNAME);
        $pageCategories             = $pageCategoryRepository->findAll();

        $this->app->response->header('Content-Type', 'application/json');
        $this->app->response->setStatus(HttpStatusCodes::OK);
        $this->app->response->setBody($this->app->serializer->serialize($pageCategories, 'json'));
    }

    public function getPageCategoryByIdAction($id) {
        $entityManager              = $this->app->entityManager;
        $pageCategoryRepository     = $entityManager->getRepository(self::$PAGE_FULL_QUALIFIED_CLASSNAME);
        $pageCategory               = $pageCategoryRepository->findOneBy(array('id' => $id));

        if ($pageCategory === null) {
            $this->app->response->setStatus(HttpStatusCodes::NOT_FOUND);
            return;
        }

        $this->app->response->header('Content-Type', 'application/json');
        $this->app->response->setStatus(HttpStatusCodes::OK);
        $this->app->response->setBody($this->app->serializer->serialize($pageCategory, 'json'));
    }

    public function updatePageCategoryAction($pageCategoryId) {
        $pageCategoryObj            = $this->app->serializer->deserialize($this->app->request->getBody(), self::$PAGE_FULL_QUALIFIED_CLASSNAME, 'json');

        // get original page category
        $entityManager              = $this->app->entityManager;
        $pageCategoryRepository     = $entityManager->getRepository(self::$PAGE_FULL_QUALIFIED_CLASSNAME);
        $origPageCategory           = $pageCategoryRepository->findOneBy(array('id' => $pageCategoryId));

        $origPageCategory->update($pageCategoryObj);

        // force update
        try {
            $entityManager->flush();
        } catch (DBALException $dbalex) {
            $now = new DateTime();
            $this->app->log->error(sprintf('[%s]: %s', $now->format('d-m-Y H:i:s'), $dbalex->getMessage()));
            $this->app->response->setStatus(HttpStatusCodes::CONFLICT);
            return;
        }

        $this->app->response->header('Content-Type', 'application/json');
        $this->app->response->setStatus(HttpStatusCodes::OK);
        $this->app->response->setBody($this->app->serializer->serialize($origPageCategory, 'json'));
    }

    public function insertPageAction() {
        $pageCategoryObj          = $this->app->serializer->deserialize($this->app->request->getBody(), self::$PAGE_FULL_QUALIFIED_CLASSNAME, 'json');

        $entityManager            = $this->app->entityManager;
        $entityManager->persist($pageCategoryObj);

        try {
            $entityManager->flush();
        } catch(DBALException $dbalex) {
            $now = new DateTime();
            $this->app->log->error(sprintf('[%s]: %s', $now->format('d-m-Y H:i:s'), $dbalex->getMessage()));
            $this->app->response->setStatus(HttpStatusCodes::CONFLICT);
            return;
        }

        $this->app->response->header('Content-Type', 'application/json');
        $this->app->response->setStatus(HttpStatusCodes::CREATED);
        $this->app->response->setBody($this->app->serializer->serialize($pageCategoryObj, 'json'));
    }

    public function deletePageCategoryByIdAction($id) {
        $entityManager              = $this->app->entityManager;
        $pageCategoryRepository     = $entityManager->getRepository(self::$PAGE_FULL_QUALIFIED_CLASSNAME);
        $pageCategory               = $pageCategoryRepository->findOneBy(array('id' => $id));

        if ($pageCategory === null) {
            $this->app->response->setStatus(HttpStatusCodes::NOT_FOUND);
            return;
        }

        $entityManager->remove($pageCategory);

        try {
            $entityManager->flush();
        } catch (DBALException $dbalex) {
            $now = new DateTime();
            $this->app->log->error(sprintf('[%s]: %s', $now->format('d-m-Y H:i:s'), $dbalex->getMessage()));
            $this->app->response->setStatus(HttpStatusCodes::CONFLICT);
        }

        $this->app->response->setStatus(HttpStatusCodes::NO_CONTENT);
    }
}