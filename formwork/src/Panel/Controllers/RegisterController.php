<?php

namespace Formwork\Panel\Controllers;

use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Exceptions\TranslatedException;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Panel\Events\PanelLoggedInEvent;
use Formwork\Schemes\Schemes;
use Formwork\Users\UserFactory;

final class RegisterController extends AbstractController
{
    /**
     * Register@register action
     */
    public function register(Schemes $schemes, UserFactory $userFactory): Response
    {
        if (!$this->site->users()->isEmpty()) {
            return $this->redirectToReferer(default: $this->generateRoute('panel.index'), base: $this->panel->panelRoot());
        }

        $this->csrfToken->generate($this->panel->getCsrfTokenName());

        $fields = $schemes->get('forms.register')->fields();

        if ($this->request->method() === RequestMethod::GET) {
            return new Response($this->view('@panel.register.register', [
                'title'  => $this->translate('panel.register.register'),
                'fields' => $fields,
            ]));
        }

        $form = $this->form('register', $fields)
            ->processRequest($this->request, uploadFiles: false);

        if (!$form->isValid()) {
            return new Response($this->view('@panel.register.register', [
                'title'  => $this->translate('panel.register.register'),
                'fields' => $form->fields(),
            ]), $form->getResponseStatus());
        }

        $user = $userFactory->make([]);

        try {
            $user->setMultiple([...$form->data()->toArray(), 'role' => 'admin']);
            $user->save();
        } catch (TranslatedException $e) {
            return $this->error($this->translate($e->getLanguageString()), ['fields' => $form->fields()]);
        } catch (InvalidValueException $e) {
            $identifier = $e->getIdentifier() ?? 'invalidFields';
            return $this->error($this->translate('panel.users.user.cannotCreate.' . $identifier), ['fields' => $form->fields()]);
        }

        $user->authenticate($form->data()->get('password'));
        $this->events->dispatch(new PanelLoggedInEvent($user, $this->request));

        return $this->redirect($this->generateRoute('panel.index'));
    }

    /**
     * Display registration form with an error notification
     *
     * @param array<string, mixed> $data
     */
    private function error(string $message, array $data = []): Response
    {
        $defaults = ['title' => $this->translate('panel.register.register'), 'error' => true];
        $this->panel->notify($message, 'error');
        return new Response($this->view('@panel.register.register', [...$defaults, ...$data]));
    }
}
