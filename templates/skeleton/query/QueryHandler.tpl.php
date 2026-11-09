<?php echo "<?php\n\n"; ?>
declare(strict_types=1);

namespace <?php echo $namespace; ?>;

use Glavweb\ApiBundle\Query\ResultSet;
use Glavweb\ApiBundle\Util\QueryHandlerTrait;
use Glavweb\DatagridBundle\Factory\NativeDatagridFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CQRS query handler for <?php echo $shortName; ?>.
<?php
if ($author) {
    $authorFull = $author;
    if ($authorEmail) {
        $authorFull .= ' <'.$authorEmail.'>';
    }
    echo " *\n * @author $authorFull\n";
}
?>
 */
class <?php echo $class_name."\n"; ?>
{
    use QueryHandlerTrait;

    public function __construct(
        private readonly NativeDatagridFactory $datagridFactory,
    ) {
    }

    public function get<?php echo $pluralShortName; ?>ResultSet(<?php echo $entitiesQueryClass->getShortName(); ?> $query): ResultSet
    {
        $datagridBuilder = $this->datagridFactory->createBuilder(
            '<?php echo $dataSchema; ?>',
            $query->scope ?: '<?php echo $listScope; ?>'
        );

        $datagridBuilder
            ->setFirstResult($query->offset)
            ->setMaxResults(min($query->limit ?: 100, 1000))
            ->setOrderings($this->getOrdering($query->sort))
            // ->addFilter('author')
        ;

        $datagrid = $datagridBuilder->build([
            // 'author' => $query->author,
        ]);

        return ResultSet::fromDatagrid($datagrid);
    }

    public function get<?php echo $shortName; ?>Data(<?php echo $entityQueryClass->getShortName(); ?> $query): array
    {
        $datagrid = $this->datagridFactory->createBuilder(
            '<?php echo $dataSchema; ?>',
            $query->scope ?: '<?php echo $viewScope; ?>'
        )
            ->addFilter('id')
            ->build(['id' => $query->id]);

        $item = $datagrid->getItem();
        if (!$item) {
            throw new NotFoundHttpException('<?php echo $shortName; ?> не найден');
        }

        return $item;
    }
}
