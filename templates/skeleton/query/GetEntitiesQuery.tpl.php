<?php echo "<?php\n\n"; ?>
declare(strict_types=1);

namespace <?php echo $namespace; ?>;

use Glavweb\ApiBundle\Attribute\FromRequest;
use Glavweb\ApiBundle\Query\ListQuery;

/**
 * @see <?php echo $handlerClass->getShortName(); ?>::<?php echo $handlerMethod; ?>()
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
class <?php echo $class_name; ?> extends ListQuery
{
    // #[FromRequest]
    // public ?int $author = null;
}
