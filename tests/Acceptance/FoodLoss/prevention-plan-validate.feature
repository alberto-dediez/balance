Feature:
        I want to make sure that the system validates properties before accessing the actions step

        Scenario: As an admin, I can't access the validate step
                Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
                Given prevention plan "Plan 1" with id "333" exists
                When I am logged as "admin@dediez.software"
                When I go to "/es/prevention-plans/edit/333/validate"
                Then the response status should be 403

        Scenario: As an owner, if all properties are valid, I am redirected to the actions step
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given property "Property 1" with id "prop1" exists
                Given company "Company 1" with id "company1" exists
                Given property "prop1" has company "company1" assigned
                Given critical point "Critical Point 1" with id "crit-1" of account "mycompany" and property "prop1" exists
                Given prevention plan "Plan 1" with id "333" of account "mycompany" with property "prop1" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/prevention-plans/edit/333/validate"
                Then I am redirected to "/es/prevention-plans/edit/333/actions"

        Scenario: As an owner, if a property has validation errors, I see the validation page
                Given account "MyCompany" with id "mycompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given property "Property 1" with id "prop1" exists
                Given prevention plan "Plan 1" with id "333" of account "mycompany" with property "prop1" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/prevention-plans/edit/333/validate"
                Then the response status should be 200
                Then I see a text "Las siguientes propiedades tienen datos incompletos"

        Scenario: As an owner, I cannot access the validate step of a plan from another account
                Given account "MyCompany" with id "mycompany" exists
                Given account "OtherCompany" with id "othercompany" exists
                Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
                Given prevention plan "Plan 1" with id "333" of account "othercompany" exists
                When I am logged as "owner@dediez.software"
                When I go to "/es/prevention-plans/edit/333/validate"
                Then the response status should be 403

        Scenario: As a normal user I can't access the validate step
                Given account "MyCompany" with id "mycompany" exists
                Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
                Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
                When I am logged as "user1@dediez.software"
                When I go to "/es/prevention-plans/edit/333/validate"
                Then the response status should be 403
