Feature:
    Ability to see the doggy bag dashboard in web

    Scenario: As an owner, I can access the doggy bag dashboard of my account
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given packaging "Tarrina" with id "packaging-1" of account "my-account" exists
        Given doggy bag sign "Cartel" with id "sign-1" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag/dashboard"
        Then the response status should be 200
        Then I see a text "Stock total"
        Then I see a text "Stock por tipo de envase"

    Scenario: As an owner, I cannot see doggy bag data of other accounts in the dashboard
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given packaging "Tarrina" with id "packaging-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/doggy-bag/dashboard"
        Then the response status should be 200
        Then I see a text "No hay datos de doggy bag para los filtros seleccionados"

    Scenario: As a normal user, I can't access the doggy bag dashboard
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/doggy-bag/dashboard"
        Then the response status should be 403

    Scenario: As an admin, I can't access the doggy bag dashboard
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/doggy-bag/dashboard"
        Then the response status should be 403
