Feature:
    Ability to call the properties API without locale

    Scenario: List properties without locale
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        Given property "Hotel" with id "111" of account "mycompany" exists
        Given property "Rural" with id "222" of account "mycompany" exists
        When I am authenticated in API as "owner@dediez.software"
        And I send a GET request to "/api/properties"
        Then the status code should be 200
        And the response JSON should be equal to:
        """
        [
            {"property_id": "111", "name": "Hotel", "account_id": "mycompany", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null, "operating_regime": "owned", "operator_email": null, "operator_name": null, "operator_locale": null},
            {"property_id": "222", "name": "Rural", "account_id": "mycompany", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null, "operating_regime": "owned", "operator_email": null, "operator_name": null, "operator_locale": null}
        ]
        """

    Scenario: Register a property without locale
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" with id "owner" of account "mycompany" exists
        When I am authenticated in API as "owner@dediez.software"
        And I send a POST request to "/api/properties" with body:
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
            "account_id": "mycompany",
            "operating_regime": "owned"
        }
        """

    Scenario: Match properties without locale
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given property "Hotel Barcelo Renacimiento" with id "111" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a POST request to "/api/properties/match" with body:
        """
        {
            "name": "Hotel Barcelo Renacimiento",
            "source": "hotelbeds",
            "external_id": "12345"
        }
        """
        Then the status code should be 200
        And the response JSON should be similar to:
        """
        {
            "property_id": "111"
        }
        """

    Scenario: Unlink the account of a property without locale
        Given account "MyCompany" with id "mycompany" exists
        Given property "Hotel" with id "333" of account "mycompany" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a "DELETE" request to "/api/properties/333/account"
        Then the status code should be 200
        And the response JSON attribute "account_id" should be null

    Scenario: List the properties of a distributor without locale
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given user "owner@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given account "Hotel A" with id "1111" exists
        Given account "Hotel B" with id "2222" exists
        Given account "Hotel C" with id "3333" exists
        Given account "3333" has feature "property_trip_calculators" disabled
        Given user "owner@hotelb.test" with role "ROLE_OWNER" of account "2222" exists
        Given property "Sede Greenhost" with id "50" of account "555" exists
        Given property "Hotel Playa" with id "11" of account "1111" exists
        Given property "Hotel Sierra" with id "12" of account "1111" exists
        Given property "Hotel Rio" with id "21" of account "2222" exists
        Given property "Hotel Lago" with id "22" of account "2222" exists
        Given property "Hotel Nube" with id "31" of account "3333" exists
        Given distributor "555" is assigned to account "1111"
        Given distributor "555" is assigned to property "21"
        Given distributor "555" is assigned to account "3333"
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a GET request to "/api/distributors/555/properties"
        Then the status code should be 200
        And the response JSON should be equal to:
        """
        [
            {"property_id": "50", "name": "Sede Greenhost", "account_id": "555", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null, "operating_regime": "owned", "operator_email": null, "operator_name": null, "operator_locale": null},
            {"property_id": "11", "name": "Hotel Playa", "account_id": "1111", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null, "operating_regime": "owned", "operator_email": null, "operator_name": null, "operator_locale": null},
            {"property_id": "12", "name": "Hotel Sierra", "account_id": "1111", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null, "operating_regime": "owned", "operator_email": null, "operator_name": null, "operator_locale": null},
            {"property_id": "21", "name": "Hotel Rio", "account_id": "2222", "property_type": "Hotel", "address": null, "postal_code": null, "destination_id": null, "country_id": null, "operating_regime": "owned", "operator_email": null, "operator_name": null, "operator_locale": null}
        ]
        """

    Scenario: Not authenticated user is denied without locale
        When I send a GET request to "/api/properties"
        Then the status code should be 403

    Scenario: Unsupported locale is still not found
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am authenticated in API as "admin@dediez.software"
        And I send a GET request to "/api/fr/properties"
        Then the status code should be 404
