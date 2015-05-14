<?php

namespace CliTools\Console\Command\Sync;

/*
 * CliTools Command
 * Copyright (C) 2015 Markus Blaschke <markus@familie-blaschke.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use CliTools\Console\Builder\SelfCommandBuilder;

class RestoreCommand extends \CliTools\Console\Command\Sync\AbstractCommand {

    /**
     * Configure command
     */
    protected function configure() {
        $this->setName('sync:restore')
             ->setDescription('Restore project files');
    }

    /**
     * Restore task
     */
    protected function runTask() {
        // ##################
        // Restore dirs
        // ##################
        $source = $this->config->share['rsync']['server'] . self::PATH_DUMP;
        $target = $this->workingPath;
        $command = $this->createShareRsyncCommand($source, $target, true);
        $command->executeInteractive();

        // ##################
        // Restore mysql dump
        // ##################
        $source = $this->config->share['rsync']['server'] . self::PATH_DUMP;
        $target = $this->tempDir;
        $command = $this->createShareRsyncCommand($source, $target, false);
        $command->executeInteractive();

        $iterator = new \DirectoryIterator($this->tempDir . '/mysql');
        foreach ($iterator as $item) {
            // skip dot
            if ($item->isDot()) {
                continue;
            }

            list($database) = explode('.', $item->getFilename(), 2);

            if (!empty($database)) {
                $this->output->writeln('<info>Restoring database ' . $database . '</info>');

                $mysqldump = new SelfCommandBuilder();
                $mysqldump->addArgumentTemplate('mysql:restore %s %s', $database, $item->getPathname());
                $mysqldump->executeInteractive();
            }
        }
    }

}