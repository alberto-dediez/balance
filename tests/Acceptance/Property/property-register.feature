Feature:
    I want to make sure that the software allow to register a property

    Scenario: As an admin, I can access to register new properties
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new properties
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/properties/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new properties
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties/register"
        When I submit a form with "name:Hotel" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Propiedad creada correctamente"

    Scenario: As an owner, I can register new properties for my account
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties/register"
        When I submit a form with "name:Hotel" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Propiedad creada correctamente"

    Scenario: As an owner, the exact location I pick sets the coordinates of the property
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given place "place-ref" named "Hotel Ritz, Madrid" at "40.4180", "-3.6940" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties/register"
        When I pick "place-ref" in the autocomplete "placeReference"
        When I submit a form with "name:Hotel Ritz|[info][placeReference]:place-ref" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then property named "Hotel Ritz" has place location "Hotel Ritz, Madrid" at "40.4180", "-3.6940"

    Scenario: As an owner, I can register a property for my account via API
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        When I am authenticated in API as "owner@dediez.software"
        And I send a POST request to "/api/es/properties" with body:
        """
        {
            "name": "Hotel Test",
            "account_id": "mycompany"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "name": "Hotel Test",
            "account_id": "mycompany"
        }
        """

    Scenario: As an owner, I cannot register a property for another account via API
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        When I am authenticated in API as "owner@dediez.software"
        And I send a POST request to "/api/es/properties" with body:
        """
        {
            "name": "Hotel Test",
            "account_id": "othercompany"
        }
        """
        Then the status code should be 403

    Scenario: Not authenticated user cannot register a property via API
        When I send a POST request to "/api/es/properties" with body:
        """
        {
            "name": "Hotel Test",
            "account_id": "mycompany"
        }
        """
        Then the status code should be 403
