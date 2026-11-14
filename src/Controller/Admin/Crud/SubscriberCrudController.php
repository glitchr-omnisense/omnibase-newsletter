<?php

namespace Base\Newsletter\Controller\Admin\Crud;

use Base\Admin\Attribute\AdminAction;
use Base\Admin\Config\Action;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filter;
use Base\Admin\Filter\Filters;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Parser;
use Base\Field\DateTimeField;
use Base\Field\IdField;
use Base\Field\TextField;
use Base\Newsletter\Entity\Subscriber;
use Base\Newsletter\Repository\SubscriberRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * The list: who signed up, from where, who confirmed, who left. Read more
 * than written - an address is added by itself confirming -, and the
 * confirmed ones exported as a CSV for whatever the site moves to next.
 */
class SubscriberCrudController extends AbstractCrudController
{
    private SubscriberRepository $subscribers;

    #[Required]
    public function setNewsletterServices(SubscriberRepository $subscribers): void
    {
        $this->subscribers = $subscribers;
    }

    public static function getEntityFqcn(): string
    {
        return Subscriber::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-address-book';
    }

    public function configureFilters(Filters $filters): Filters
    {
        // Not columns but states: a date set or not. Said in the core's finder
        // language, which every persistence reads (matchWith(); SubscriberRepository
        // reads it).
        $yes = static fn (mixed $value): bool => '1' === $value || 'true' === $value || true === $value;
        $isSet = static fn (string $field) => static fn (mixed $value) => Criterion::of($field, null, $yes($value) ? Parser::OPTION_NOT_NULL : Parser::OPTION_NULL);

        return $filters
            ->add(Filter::new('confirmed', '@newsletter.admin.subscriber.confirmed')->asBoolean()->matchWith($isSet('confirmedAt')))
            ->add(Filter::new('unsubscribed', '@newsletter.admin.subscriber.unsubscribed')->asBoolean()->matchWith($isSet('unsubscribedAt')))
            ->add('locale')
            ->add('source');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('email', '@newsletter.admin.subscriber.email')->setColumns(6);
        yield TextField::new('locale', '@newsletter.admin.subscriber.locale')->setColumns(2);
        yield TextField::new('source', '@newsletter.admin.subscriber.source')->setColumns(4);
        yield DateTimeField::new('createdAt', '@newsletter.admin.subscriber.created_at')->hideOnForm();
        yield DateTimeField::new('confirmedAt', '@newsletter.admin.subscriber.confirmed_at')->hideOnForm();
        yield DateTimeField::new('unsubscribedAt', '@newsletter.admin.subscriber.unsubscribed_at')->hideOnForm();
        yield TextField::new('ip', '@newsletter.admin.subscriber.ip')->onlyOnDetail();
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->add(Actions::PAGE_INDEX, Action::new('export', '@newsletter.admin.subscriber.action.export', 'fa-solid fa-file-csv')
            ->createAsGlobalAction()
            ->linkToCrudAction('export'));

        return $actions;
    }

    /** The confirmed addresses still on the list, as a CSV: e-mail, language, since, source. */
    #[AdminAction('/export', methods: ['GET'])]
    public function export(): Response
    {
        $subscribers = $this->subscribers->findConfirmed();
        $response = new StreamedResponse(function () use ($subscribers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'locale', 'confirmed_at', 'source'], ',', '"', '');
            foreach ($subscribers as $subscriber) {
                fputcsv($out, [$subscriber->getEmail(), $subscriber->getLocale(), $subscriber->getConfirmedAt()?->format('Y-m-d H:i:s'), $subscriber->getSource()], ',', '"', '');
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="newsletter-%s.csv"', date('Y-m-d')));

        return $response;
    }
}
