<?php
/**
 * @var Glavweb\ApiBundle\Maker\Helper\ApiControllerHelper $helper
 * @var Symfony\Bundle\MakerBundle\Util\ClassNameDetails   $modelClass
 * @var array                                              $fields
 * @var array                                              $uploadableFields
 * @var array                                              $enumFields
 * @var array                                              $associations
 * @var string                                             $author
 * @var string                                             $authorEmail
 */
$dataSchema = $helper->getDataSchemaName($modelClass->getFullName());
$scope = $helper->getScopeName($modelClass->getFullName(), 'view');
$route = '/'.$helper->plural($helper->lowerDash(str_replace('\\', '/', $modelClass->getRelativeName()))).'/{'.$helper->lowerFirst($modelClass->getShortName()).'}';
$routeName = 'api_get_'.$helper->tableize($modelClass->getRelativeName());
$roleEntity = strtoupper($helper->tableize($modelClass->getShortName()));
?>
    /**
     * Returns <?php echo $helper->lowerWords($modelClass->getShortName())."\n"; ?>
     */
    #[Input(class: "<?php echo $entityQueryClass->getFullName(); ?>", in: "query")]
    #[Output(dataSchema: "<?php echo $dataSchema; ?>", scope: "<?php echo $scope; ?>")]
    #[Scope(name: "view", path: "<?php echo $scope; ?>")]
    #[Route("<?php echo $route; ?>", name: "<?php echo $routeName; ?>", methods: ["GET"])]
    #[OA\Get(tags: ['<?php echo $modelClass->getShortName(); ?>'], parameters: [new OA\Parameter(name: '$scope', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Область видимости данных')], responses: [new OA\Response(response: 200, description: 'Запрос выполнен успешно.'), new OA\Response(response: 403, description: 'Доступ запрещен.')])]
    #[IsGranted("ROLE_<?php echo $roleEntity; ?>_VIEW")]
    public function get<?php echo $modelClass->getShortName(); ?>Action(<?php echo $modelClass->getShortName(); ?> $<?php echo $helper->lowerFirst($modelClass->getShortName()); ?>, Request $request, ScopeFetcherInterface $scopeFetcher, <?php echo $queryHandler->getShortName(); ?> $queryHandler): JsonResponse
    {
        $query = new <?php echo $entityQueryClass->getShortName(); ?>(
            $<?php echo $helper->lowerFirst($modelClass->getShortName()); ?>->getId(),
            $scopeFetcher->getAvailable(RequestBag::get($request, '$scope'))
        );
        $data = $queryHandler->get<?php echo $modelClass->getShortName(); ?>Data($query);

        return new JsonResponse($data);
    }
