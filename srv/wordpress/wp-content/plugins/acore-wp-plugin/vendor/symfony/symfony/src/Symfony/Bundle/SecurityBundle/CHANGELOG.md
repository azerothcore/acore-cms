CHANGELOG
=========

3.4.0
-----

 * Added new `security.helper` service that is an instance of `Symfony\Component\Security\Core\Security`
   and provides shortcuts for common security tasks.
 * Tagging voters with the `security.voter` tag without implementing the
   `VoterInterface` on the class is now deprecated and will be removed in 4.0.
 * [BC BREAK] `FirewallContext::getListeners()` now returns `\Traversable|array`
 * added info about called security listeners in profiler
 * Added `logout_on_user_change` to the firewall options. This config item will
   trigger a logout when the user has changed. Should be set to true to avoid
   deprecations in the configuration.
 * deprecated HTTP digest authentication
 * deprecated command `acl:set` along with `SetAclCommand` class
 * deprecated command `init:acl` along with `InitAclCommand` class
 * Added support for the new Argon2i password encoder
 * added `stateless` option to the `switch_user` listener
 * deprecated auto picking the first registered provider when no configured provider on a firewall and ambiguous

3.3.0
-----

 * Deprecated instantiating `UserPasswordEncoderCommand` without its constructor
   arguments fully provided.
 * Deprecated `UserPasswordEncoderCommand::getContainer()` and relying on the
  `ContainerAwareCommand` sub class or `ContainerAwareInterface` implementation for this command.
 * Deprecated the `FirewallMap::$map` and `$container` properties.
 * [BC BREAK] Keys of the `users` node for `in_memory` user provider are no longer normalized.
 * deprecated `FirewallContext::getListeners()`

3.2.0
-----

 * Added the `SecurityUserValueResolver` to inject the security users in actions via
   `Symfony\Component\Security\Core\User\UserInterface` in the method signature.

3.0.0
-----

 * Removed the `security.context` service.

2.8.0
-----

 * deprecated the `key` setting of `anonymous`, `remember_me` and `http_digest`
   in favor of the `secret` setting.
 * deprecated the `intention` firewall listener setting in favor of the `csrf_token_id`.

2.6.0
-----

 * Added the possibility to override the default success/failure handler
   to get the provider key and the options injected
 * Deprecated the `security.context` service for the `security.token_storage` and
   `security.authorization_checker` services.

2.4.0
-----

 * Added 'host' option to firewall configuration
 * Added 'csrf_token_generator' and 'csrf_token_id' options to firewall logout
   listener configuration to supersede/alias 'csrf_provider' and 'intention'
   respectively
 * Moved 'security.secure_random' service configuration to FrameworkBundle

2.3.0
-----

 * allowed for multiple IP address in security access_control rules

2.2.0
-----

 * Added PBKDF2 Password encoder
 * Added BCrypt password encoder

2.1.0
-----

 * [BC BREAK] The custom factories for the firewall configuration are now
   registered during the build method of bundles instead of being registered
   by the end-user (you need to remove the 'factories' keys in your security
   configuration).

 * [BC BREAK] The Firewall listener is now registered after the Router one. This
   means that specific Firewall URLs (like /login_check and /logout must now
   have proper route defined in your routing configuration)

 * [BC BREAK] refactored the user provider configuration. The configuration
   changed for the chain provider and the memory provider:

    Before:

    ``` yaml
    security:
        providers:
            my_chain_provider:
                providers: [my_memory_provider, my_doctrine_provider]
            my_memory_provider:
                users:
                    toto: { password: foobar, roles: [ROLE_USER] }
                    foo: { password: bar, roles: [ROLE_USER, ROLE_ADMIN] }
    ```

    After:

    ``` yaml
    security:
        providers:
            my_chain_provider:
                chain:
                    providers: [my_memory_provider, my_doctrine_provider]
            my_memory_provider:
                memory:
                    users:
                        toto: { password: foobar, roles: [ROLE_USER] }
                        foo: { password: bar, roles: [ROLE_USER, ROLE_ADMIN] }
    ```

 * [BC BREAK] Method `equals` was removed from `UserInterface` to its own new
   `EquatableInterface`. The user class can now implement this interface to override
   the default implementation of users equality test.

 * added a validator for the user password
 * added 'erase_credentials' as a configuration key (true by default)
 * added new events: `security.authentication.success` and `security.authentication.failure`
   fired on authentication success/failure, regardless of authentication method,
   events are defined in new event class: `Symfony\Component\Security\Core\AuthenticationEvents`.

 * Added optional CSRF protection to LogoutListener:

    ``` yaml
    security:
        firewalls:
            default:
                logout:
                    path: /logout_path
                    target: /
                    csrf_parameter: _csrf_token                   # Optional (defaults to "_csrf_token")
                    csrf_provider:  security.csrf.token_generator # Required to enable protection
                    intention:      logout                        # Optional (defaults to "logout")
    ```

    If the LogoutListener has CSRF protection enabled but cannot validate a token,
   then a LogoutException will be thrown.

 * Added `logout_url` templating helper and Twig extension, which may be used to
   generate logout URL's within templates. The security firewall's config key
   must be specified. If a firewall's logout listener has CSRF protection
   enabled, a token will be automatically added to the generated URL.
