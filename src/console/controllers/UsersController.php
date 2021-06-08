<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\Model;
use craft\console\Controller;
use craft\elements\User;
use craft\helpers\Console;
use yii\console\ExitCode;

/**
 * Manages user accounts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class UsersController extends Controller
{
    /**
     * @var string|null The created user’s email
     */
    public $email;

    /**
     * @var string|null The created user’s username
     */
    public $username;

    /**
     * @var string|null The user’s new password, or created user's password
     */
    public $password;

    /**
     * @var bool|null Create the user as an admin
     */
    public $admin;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'create':
                $options[] = 'email';
                $options[] = 'username';
                $options[] = 'password';
                $options[] = 'admin';
                break;
            case 'set-password':
                $options[] = 'password';
                break;
        }

        return $options;
    }

    /**
     * Lists admin users.
     *
     * @return int
     */
    public function actionListAdmins(): int
    {
        $users = User::find()
            ->admin()
            ->anyStatus()
            ->orderBy(['username' => SORT_ASC])
            ->all();
        $total = count($users);
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $this->stdout("$total admin " . ($total === 1 ? 'user' : 'users') . ' found:' . PHP_EOL, Console::FG_YELLOW);

        foreach ($users as $user) {
            $this->stdout('    - ');
            if ($generalConfig->useEmailAsUsername) {
                $this->stdout($user->email);
            } else {
                $this->stdout("$user->username ($user->email)");
            }
            switch ($user->getStatus()) {
                case User::STATUS_SUSPENDED:
                    $this->stdout(" [suspended]", Console::FG_RED);
                    break;
                case User::STATUS_ARCHIVED:
                    $this->stdout(" [archived]", Console::FG_RED);
                    break;
                case User::STATUS_PENDING:
                    $this->stdout(" [pending]", Console::FG_YELLOW);
                    break;
            }
            $this->stdout(PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Creates a user.
     *
     * @return int
     */
    public function actionCreate(): int
    {
        // Validate the arguments
        $attributesFromArgs = array_filter([
            'email' => $this->email,
            'username' => $this->username,
            'newPassword' => $this->password,
            'admin' => $this->admin,
        ], function($v) {
            return $v !== null;
        });

        $user = new User($attributesFromArgs);

        if (!$user->validate(array_keys($attributesFromArgs))) {
            $this->stderr('Invalid arguments:' . PHP_EOL . '    - ' . implode(PHP_EOL . '    - ', $user->getFirstErrors()) . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $user->username = $this->email ?: $this->prompt('Email:', [
                'required' => true,
                'validator' => $this->_createInputValidator($user, 'email'),
            ]);
        } else {
            $user->email = $this->email ?: $this->prompt('Email:', [
                'required' => true,
                'validator' => $this->_createInputValidator($user, 'email'),
            ]);
            $user->username = $this->username ?: $this->prompt('Username:', [
                'required' => true,
                'validator' => $this->_createInputValidator($user, 'username'),
            ]);
        }

        $user->admin = $this->admin ?? $this->confirm('Make this user an admin?', $this->admin);
        $user->newPassword = $this->password ?: $this->passwordPrompt([
            'validator' => $this->_createInputValidator($user, 'newPassword'),
        ]);

        $this->stdout('Saving the user ... ');
        Craft::$app->getElements()->saveElement($user, false);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Changes a user’s password.
     *
     * @param string $usernameOrEmail The user’s username or email address
     * @return int
     */
    public function actionSetPassword(string $usernameOrEmail): int
    {
        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($usernameOrEmail);

        if (!$user) {
            $this->stderr("No user exists with a username/email of “{$usernameOrEmail}”." . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->setScenario(User::SCENARIO_PASSWORD);

        if ($this->password) {
            $user->newPassword = $this->password;
            if (!$user->validate()) {
                $this->stderr('Unable to set new password on user: ' . $user->getFirstError('newPassword') . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            $this->passwordPrompt([
                'validator' => $this->_createInputValidator($user, 'newPassword'),
            ]);
        }

        $this->stdout('Saving the user ... ');
        Craft::$app->getElements()->saveElement($user, false);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Creates a validator function for `validator` option of `Controller::prompt`.
     *
     * @param Model $model
     * @param string $attribute
     * @param string|null $error
     * @return callable
     */
    private function _createInputValidator(Model $model, string $attribute, &$error = null): callable
    {
        return function($input, &$error) use($model, $attribute) {
            $model->$attribute = $input;

            if (!$model->validate([$attribute])) {
                $error = $model->getFirstError($attribute);

                return false;
            }
            $error = null;

            return true;
        };
    }
}
