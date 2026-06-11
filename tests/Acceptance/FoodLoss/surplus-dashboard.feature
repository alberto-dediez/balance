Feature:
    Ability to see the surplus dashboard in web

    Scenario: As an owner, I can access the surplus dashboard of my account
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "my-account" exists
        Given surplus with id "surplus-2" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/dashboard"
        Then the response status should be 200
        Then I see a text "Excedentes declarados"
        Then I see a text "Clasificación por prioridad de destino"

    Scenario: As an owner, I cannot see surpluses of other accounts in the dashboard
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given surplus with id "surplus-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/surpluses/dashboard"
        Then the response status should be 200
        Then I see a text "No hay excedentes declarados para los filtros seleccionados"

    Scenario: As a normal user, I can't access the surplus dashboard
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/surpluses/dashboard"
        Then the response status should be 403

    Scenario: As an admin, I can't access the surplus dashboard
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/surpluses/dashboard"
        Then the response status should be 403
