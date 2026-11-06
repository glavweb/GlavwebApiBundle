<?php
/**
 * @var Glavweb\ApiBundle\Maker\DTO\ControllerMethodDTO[] $methods
 * @var array                                             $namespaces
 */
?>
<?php echo "<?php\n\n"; ?>
declare(strict_types=1);

namespace <?php echo $namespace; ?>;

use App\Controller\Api\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
<?php
foreach ($namespaces as $additionalNamespace) {
    echo "use $additionalNamespace;\n";
}
?>

/**
 * Class <?php echo $class_name."\n"; ?>
<?php
if ($author) {
    $authorFull = $author;
    if ($authorEmail) {
        $authorFull .= ' <'.$authorEmail.'>';
    }

    echo " *\n";
    echo " * @author $authorFull\n";
}
?>
 */
class <?php echo $class_name; ?> extends AbstractController
{
<?php
foreach ($methods as $methodDTO) {
    include __DIR__.'/'.$methodDTO->getTemplate();
    echo "\n";
}
?>
}
