Feature:
    I want to make sure that users can create api tokens from their profile

    Scenario: As a user of an account that allows api tokens, I can create a token and see its value once
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens/register"
        Then the response status should be 200
        Then I see a form
        When I submit a form with "name:Integration" and button "Crear token"
        Then the response status should be 200
        Then the response header "Cache-Control" should be "max-age=0, must-revalidate, no-store, private"
        Then I see a text "Copia el token ahora. No podrás volver a verlo"
        Then I see a text "hyd_"
        Then I see a text "Integration"
        Then user "user@dediez.software" should have 1 active api tokens

    Scenario: As a user, I can not create a token with a past expiration date
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens/register"
        When I submit a form with "name:Integration|expiresAt:2020-01-01" and button "Crear token"
        Then the response status should be 200
        Then I see a text "Este valor debería ser mayor que"
        Then I do not see a text "Copia el token ahora"
        Then user "user@dediez.software" should have 0 active api tokens

    Scenario: As a user with the maximum of active tokens, I can not create another one
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has 10 active api tokens
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens/register"
        When I submit a form with "name:Integration" and button "Crear token"
        Then I see a text "Has alcanzado el máximo de 10 tokens activos. Revoca alguno para crear otro"
        Then I do not see a text "Copia el token ahora"
        Then user "user@dediez.software" should have 10 active api tokens

    Scenario: As a user of an account that does not allow api tokens, I can not create tokens
        Given account "MyCompany" with id "333" exists
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens/register"
        Then the response status should be 403

    Scenario: As an admin without account, I can create a token
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/profile/api-tokens/register"
        When I submit a form with "name:Integration" and button "Crear token"
        Then the response status should be 200
        Then I see a text "Copia el token ahora. No podrás volver a verlo"
        Then user "admin@dediez.software" should have 1 active api tokens
