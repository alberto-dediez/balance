Feature:
    I want to make sure that users can list their api tokens

    Scenario: As a user, I see my tokens with their status and prefix but not their value
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_listactive_000000000000000000000000000000" named "Active integration"
        Given user "user@dediez.software" has a revoked api token "hyd_listrevoke_000000000000000000000000000000" named "Revoked integration"
        Given user "user@dediez.software" has an expired api token "hyd_listexpire_000000000000000000000000000000" named "Expired integration"
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens"
        Then the response status should be 200
        Then I see a table with 4 rows
        Then I see a text "Active integration"
        Then I see a text "hyd_listacti"
        Then I do not see a text "hyd_listactive_000000000000000000000000000000"
        Then I see a text "Activo"
        Then I see a text "Revocado"
        Then I see a text "Caducado"
        Then I see a text "Sin caducidad"
        Then I see a text "Nunca"

    Scenario: As a user, I do not see the tokens of another user
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "other@dediez.software" with role "ROLE_USER" of account "333" exists
        Given user "user@dediez.software" has an api token "hyd_mine_00000000000000000000000000000000000" named "My integration"
        Given user "other@dediez.software" has an api token "hyd_other_0000000000000000000000000000000000" named "Other integration"
        When I am logged as "user@dediez.software"
        When I go to "/es/profile/api-tokens"
        Then the response status should be 200
        Then I see a text "My integration"
        Then I do not see a text "Other integration"

    Scenario: As a user of an account that allows api tokens, I see the menu entry
        Given account "MyCompany" with id "333" allows api tokens
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        Then I see a text "Acceso a la API sin contraseña"

    Scenario: As a user of an account that does not allow api tokens, I can not see my tokens
        Given account "MyCompany" with id "333" exists
        Given user "user@dediez.software" with role "ROLE_USER" of account "333" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/profile"
        Then I do not see a text "Acceso a la API sin contraseña"
        When I go to "/es/profile/api-tokens"
        Then the response status should be 403
