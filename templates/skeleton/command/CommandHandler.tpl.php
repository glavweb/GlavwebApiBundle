<?php echo "<?php\n\n"; ?>
declare(strict_types=1);

namespace <?php echo $namespace; ?>;

use <?php echo $modelClass->getFullName(); ?>;
use <?php echo $managerClass->getFullName(); ?>;
use Glavweb\ApiBundle\Util\Populator;
use Glavweb\FrameworkBundle\Exception\DeleteEntityException;
use Glavweb\FrameworkBundle\Exception\InvalidEntityException;

/**
 * CQRS handler for <?php echo $shortName; ?> commands.
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
    public function __construct(
        private readonly <?php echo $managerClass->getShortName(); ?> $manager,
        private readonly Populator $populator,
    ) {
    }

    /**
     * @throws InvalidEntityException
     */
    public function create<?php echo $shortName; ?>(<?php echo $createCommandClass->getShortName(); ?> $command): <?php echo $shortName."\n"; ?>
    {
        $<?php echo $varName; ?> = new <?php echo $shortName; ?>();
        $this->populator->fromCommand($<?php echo $varName; ?>, $command, false);

        $this->manager->create($<?php echo $varName; ?>);

        return $<?php echo $varName; ?>;
    }

    /**
     * @throws InvalidEntityException
     */
    public function update<?php echo $shortName; ?>(<?php echo $updateCommandClass->getShortName(); ?> $command): <?php echo $shortName."\n"; ?>
    {
        $<?php echo $varName; ?> = $command->get<?php echo $shortName; ?>();
        $this->populator->fromCommand($<?php echo $varName; ?>, $command, true);

        $this->manager->update($<?php echo $varName; ?>);

        return $<?php echo $varName; ?>;
    }

    /**
     * @throws DeleteEntityException
     */
    public function delete<?php echo $shortName; ?>(<?php echo $deleteCommandClass->getShortName(); ?> $command): void
    {
        $this->manager->delete($command->get<?php echo $shortName; ?>());
    }
}
