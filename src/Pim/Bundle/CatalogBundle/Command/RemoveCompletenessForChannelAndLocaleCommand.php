<?php

declare(strict_types=1);

namespace Pim\Bundle\CatalogBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to remove completeness for channel and locale.
 *
 * @see https://akeneo.atlassian.net/browse/PIM-7155
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RemoveCompletenessForChannelAndLocaleCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setHidden(true)
            ->setName('pim:catalog:remove-completeness-for-channel-and-locale')
            ->setDescription('When a channel is updated, products completenesses and locales need to be cleaned.')
            ->addArgument(
                'channel-code',
                InputArgument::REQUIRED,
                'Channel code'
            )
            ->addArgument(
                'locales-identifier',
                InputArgument::REQUIRED,
                'locale to remove completeness'
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'user to notify'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rawLocales = stripcslashes($input->getArgument('locales-identifier'));
        $localesIdentifiers = json_decode($rawLocales, true);
        $channelCode = $input->getArgument('channel-code');
        $username = $input->getArgument('username');
        $notifier = $this->getContainer()->get('pim_notification.notifier');
        $notificationFactory = $this->getContainer()->get('pim_notification.factory.notification');
        $output->writeln(
            sprintf(
                '<info>Locales "%s" are removed from channel "%s". ' .
                'Removing all related completenesses from products.</info>',
                implode(', ', $localesIdentifiers),
                $channelCode
            )
        );

        $pushNotif = $notificationFactory->create();
        $pushNotif
            ->setType('warning')
            ->setMessage('pim_enrich.notification.settings.remove_completeness_for_channel_and_locale.start')
            ->setContext([
                'actionType' => 'settings',
                'showReportButton' => false
            ]);
        $notifier->notify($pushNotif, [$username]);
        $output->writeln(
            sprintf('<info>User "%s" has been notified completenesses removal started.</info>', $username)
        );

        $channel = $this->getContainer()->get('pim_catalog.repository.channel')
            ->findOneByIdentifier($channelCode);

        $locales = $this->getContainer()->get('pim_catalog.repository.locale')
            ->findBy(['code' => $localesIdentifiers]);

        $completenessRemover = $this->getContainer()->get('pim_catalog.remover.completeness');

        foreach ($locales as $locale) {
            $locale->removeChannel($channel);
            $completenessRemover->removeForChannelAndLocale($channel, $locale);
        }

        if (!empty($locales)) {
            $this->getContainer()->get('pim_catalog.saver.locale')->saveAll($locales);
        }
        $output->writeln('<info>Related products completenesses removal done.</info>');

        $doneNotif = $notificationFactory->create();
        $doneNotif
            ->setType('success')
            ->setMessage('pim_enrich.notification.settings.remove_completeness_for_channel_and_locale.done')
            ->setContext([
                'actionType' => 'settings',
                'showReportButton' => false
            ]);
        $notifier->notify($doneNotif, [$username]);
        $output->writeln(
            sprintf('<info>User "%s" has been notified completenesses removal is finished.</info>', $username)
        );
    }
}
