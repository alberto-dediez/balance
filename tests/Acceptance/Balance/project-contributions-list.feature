Feature:
    Ability for an account and for the superadmin to review the contributions made to their projects

    Background:
        Given account "MyCompany" with id "666" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"

    Scenario: As an owner I see the contributions of my account
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/contributions"
        Then the response status should be 200
        Then I see a text "Aportaciones a proyectos"
        Then I see a text "Reforestación"
        Then I see a text "Guest"

    Scenario: As an owner I see the detail of a contribution
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/contributions/C1"
        Then the response status should be 200
        Then I see a text "Desglose económico"
        Then I see a text "Datos fiscales del aportante"
        Then I see a text "guest@example.com"

    Scenario: As an owner I cannot see the contributions of another account
        Given account "OtherCompany" with id "999" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "999" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/credits/contributions/C1"
        Then the response status should be 403

    Scenario: As an admin I see the contributions of every account
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contributions"
        Then the response status should be 200
        Then I see a text "Reforestación"
        Then I see a text "MyCompany"

    Scenario: As an owner I cannot reach the admin list
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/contributions"
        Then the response status should be 403

    Scenario: As an admin I see the detail of any contribution
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/contributions/C1"
        Then the response status should be 200
        Then I see a text "Desglose económico"
        Then I see a text "MyCompany"

    Scenario: The contribution list can be filtered by status
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/contributions?status=paid"
        Then the response status should be 200
        Then I do not see a text "guest@example.com"
