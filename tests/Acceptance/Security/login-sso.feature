Feature:
    Users can sign in with an external identity provider

    Scenario: The login page offers the enabled identity providers
        When I go to "/es/login"
        Then the response status should be 200
        Then I see a link with name "Continuar con Google"
        Then I see a link with name "Continuar con Microsoft"
        When I go to "/en/login"
        Then the response status should be 200
        Then I see a link with name "Continue with Google"
        Then I see a link with name "Continue with Microsoft"

    Scenario: A disabled identity provider is not offered
        Given the SSO provider "microsoft" is disabled
        When I go to "/es/login"
        Then I see a link with name "Continuar con Google"
        Then I do not see a text "Continuar con Microsoft"

    Scenario: Starting the flow redirects to the identity provider
        When I go to "/login/sso/google"
        Then the response status should be 302

    Scenario: Starting the flow with a disabled provider is not found
        Given the SSO provider "google" is disabled
        When I go to "/login/sso/google"
        Then the response status should be 404

    Scenario: Starting the flow with an unknown provider is not found
        When I go to "/login/sso/github"
        Then the response status should be 404

    Scenario: A known email signs the user in
        Given user "user@dediez.software" with role "ROLE_USER" exists
        And the SSO provider "google" identifies the user as "user@dediez.software"
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/"
        When I go to "/es/"
        Then the response status should be 200

    Scenario: A signed in user lands on the page they were visiting
        Given user "user@dediez.software" with role "ROLE_USER" exists
        And the SSO provider "microsoft" identifies the user as "user@dediez.software"
        And the page "/es/profile" was requested before signing in
        When I go to "/login/sso/microsoft/callback"
        Then I am redirected to "/es/profile"

    Scenario: An unknown email is rejected
        Given the SSO provider "google" identifies the user as "nobody@dediez.software"
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/login"
        Then I see a text "No existe ningún usuario con el email del proveedor de identidad."

    Scenario: A user of an inactive account is rejected
        Given inactive account "Inactive" with id "1111" exists
        And user "user@dediez.software" with role "ROLE_USER" of account "1111" exists
        And the SSO provider "google" identifies the user as "user@dediez.software"
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/login"
        Then I see a text "No existe ningún usuario con el email del proveedor de identidad."

    Scenario: A failure at the identity provider is reported
        Given the SSO provider "google" fails
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/es/login"
        Then I see a text "No se ha podido completar el acceso con el proveedor de identidad."

    Scenario: A failure returns to the login of the language the flow started from
        Given the SSO provider "google" fails
        When I go to "/login/sso/google?locale=en"
        Then the response status should be 302
        When I go to "/login/sso/google/callback"
        Then I am redirected to "/en/login"
        Then I see a text "Sign-in with the identity provider could not be completed."

    Scenario: The callback of an unknown provider is rejected
        When I go to "/login/sso/github/callback"
        Then I am redirected to "/es/login"
        Then I see a text "No se ha podido completar el acceso con el proveedor de identidad."
