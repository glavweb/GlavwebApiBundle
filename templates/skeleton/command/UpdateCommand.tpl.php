<?php echo "<?php\n\n"; ?>
declare(strict_types=1);

namespace <?php echo $namespace; ?>;

use <?php echo $modelClass->getFullName(); ?>;
use Glavweb\ApiBundle\Attribute\Entity;
use Glavweb\ApiBundle\Attribute\EntityField;
use Glavweb\ApiBundle\Attribute\FromRequest;
use Symfony\Component\Validator\Constraints as Assert;

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
class <?php echo $class_name."\n"; ?>
{
    #[Entity(name: '<?php echo $varName; ?>', class: <?php echo $shortName; ?>::class)]
    #[Assert\NotBlank]
    public int $id;

    private <?php echo $shortName; ?> $<?php echo $varName; ?>;

    // #[FromRequest]
    // #[EntityField]
    // public string $name = '';

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function get<?php echo $shortName; ?>(): <?php echo $shortName."\n"; ?>
    {
        return $this-><?php echo $varName; ?>;
    }
}
