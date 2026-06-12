Feature:
    Ability to see the prevention plan dashboard in web

    Scenario: As an owner, I can access the prevention plan dashboard of my account
        Given account "MyAccount" with id "my-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given prevention plan "Plan A" with id "plan-1" of account "my-account" exists
        Given prevention plan "Plan B" with id "plan-2" of account "my-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/dashboard"
        Then the response status should be 200
        Then I see a text "Con responsable"
        Then I see a text "Planes de prevención por estado"

    Scenario: As an owner, I cannot see prevention plans of other accounts in the dashboard
        Given account "MyAccount" with id "my-account" exists
        Given account "OtherAccount" with id "other-account" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
        Given prevention plan "Plan A" with id "plan-1" of account "other-account" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/dashboard"
        Then the response status should be 200
        Then I see a text "No hay planes de prevención para los filtros seleccionados"

    Scenario: As a normal user, I can't access the prevention plan dashboard
        Given account "MyAccount" with id "my-account" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "my-account" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/prevention-plans/dashboard"
        Then the response status should be 403

    Scenario: As an admin, I can't access the prevention plan dashboard
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/prevention-plans/dashboard"
        Then the response status should be 403
