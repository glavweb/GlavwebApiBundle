<?php echo "<?php\n\n"; ?>
declare(strict_types=1);

namespace <?php echo $namespace; ?>;

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
    public int $id;

    public ?string $scope;

    public function __construct(int $id, ?string $scope = null)
    {
        $this->id = $id;
        $this->scope = $scope;
    }
}
