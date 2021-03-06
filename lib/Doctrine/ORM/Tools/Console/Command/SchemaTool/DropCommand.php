<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools\Console\Command\SchemaTool;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Command to drop the database schema for a set of classes based on their mappings.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class DropCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
        ->setName('orm:schema-tool:drop')
        ->setDescription(
            'Drop the complete database schema of EntityManager Storage Connection or generate the corresponding SQL output.'
        )
        ->setDefinition(array(
            new InputOption(
                'dump-sql', null, InputOption::VALUE_NONE,
                'Instead of try to apply generated SQLs into EntityManager Storage Connection, output them.'
            ),
            new InputOption(
                'force', null, InputOption::VALUE_NONE,
                "Don't ask for the deletion of the database, but force the operation to run."
            ),
            new InputOption(
                'full-database', null, InputOption::VALUE_NONE,
                'Instead of using the Class Metadata to detect the database table schema, drop ALL assets that the database contains.'
            ),
        ))
        ->setHelp(<<<EOT
Processes the schema and either drop the database schema of EntityManager Storage Connection or generate the SQL output.
Beware that the complete database is dropped by this command, even tables that are not relevant to your metadata model.
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas)
    {
        $isFullDatabaseDrop = $input->getOption('full-database');

        if ($input->getOption('dump-sql')) {
            if ($isFullDatabaseDrop) {
                $sqls = $schemaTool->getDropDatabaseSQL();
            } else {
                $sqls = $schemaTool->getDropSchemaSQL($metadatas);
            }
            $output->writeln(implode(';' . PHP_EOL, $sqls));

            return 0;
        }

        if ($input->getOption('force')) {
            $output->writeln('Dropping database schema...');

            if ($isFullDatabaseDrop) {
                $schemaTool->dropDatabase();
            } else {
                $schemaTool->dropSchema($metadatas);
            }

            $output->writeln('Database schema dropped successfully!');

            return 0;
        }

        $output->writeln('ATTENTION: This operation should not be executed in a production environment.' . PHP_EOL);

        if ($isFullDatabaseDrop) {
            $sqls = $schemaTool->getDropDatabaseSQL();
        } else {
            $sqls = $schemaTool->getDropSchemaSQL($metadatas);
        }

        if (count($sqls)) {
            $output->writeln('Schema-Tool would execute ' . count($sqls) . ' queries to drop the database.');
            $output->writeln('Please run the operation with --force to execute these queries or use --dump-sql to see them.');

            return 1;
        }

        $output->writeln('Nothing to drop. The database is empty!');
    }
}
