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
$scope = $helper->getScopeName($modelClass->getFullName(), 'list');
$roleEntity = strtoupper($helper->tableize($modelClass->getShortName()));
?>
    /**
     * Returns collection of <?php echo $helper->lowerWords($helper->plural($modelClass->getShortName()))."\n"; ?>
     */
    #[Input(class: "<?php echo $entitiesQueryClass->getFullName(); ?>", in: "query")]
    #[Output(dataSchema: "<?php echo $dataSchema; ?>", scope: "<?php echo $scope; ?>")]
    #[Scope(name: "list", path: "<?php echo $scope; ?>")]
    #[Route("/<?php echo $helper->getRoute($modelClass); ?>", name: "<?php echo $helper->getRouteName($modelClass, 'get'); ?>", methods: ["GET"])]
    #[OA\Get(tags: ['<?php echo $modelClass->getShortName(); ?>'], parameters: [new OA\Parameter(name: '$scope', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Область видимости данных')], responses: [new OA\Response(response: 200, description: 'Запрос выполнен успешно.'), new OA\Response(response: 403, description: 'Доступ запрещен.')])]
    #[IsGranted("ROLE_<?php echo $roleEntity; ?>_LIST")]
    public function get<?php echo $helper->plural($modelClass->getShortName()); ?>Action(Request $request, ScopeFetcherInterface $scopeFetcher, Populator $populator, <?php echo $queryHandler->getShortName(); ?> $queryHandler): Response
    {
        $query = new <?php echo $entitiesQueryClass->getShortName(); ?>(
            $scopeFetcher->getAvailable(RequestBag::get($request, '$scope'))
        );
        $populator->fromRequest($query, $request);
        $resultSet = $queryHandler->get<?php echo $helper->plural($modelClass->getShortName()); ?>ResultSet($query);

        return ResultSetResponse::fromResultSet($resultSet);
    }
