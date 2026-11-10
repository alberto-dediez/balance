Feature:
    Ability to list the own and distributed properties of a distributor via API

    Background:
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

    Scenario: As admin I can list the own and distributed properties of a distributor via API
        When I am authenticated in API as "admin@dediez.software"
        And I send a GET request to "/api/es/distributors/555/properties"
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

    Scenario: As the distributor owner I can list my own and distributed properties via API
        When I am authenticated in API as "owner@greenhost.test"
        And I send a GET request to "/api/es/distributors/555/properties"
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

    Scenario: As owner of another account I cannot list the properties of a distributor via API
        When I am authenticated in API as "owner@hotelb.test"
        And I send a GET request to "/api/es/distributors/555/properties"
        Then the status code should be 403

    Scenario: As owner of another distributor I cannot list the properties of a distributor via API
        Given account "Agencia" with id "777" exists
        Given account "777" is a distributor
        Given user "owner@agencia.test" with role "ROLE_OWNER" of account "777" exists
        When I am authenticated in API as "owner@agencia.test"
        And I send a GET request to "/api/es/distributors/555/properties"
        Then the status code should be 403

    Scenario: As a user of the distributor I cannot list its properties via API
        Given user "user@greenhost.test" with role "ROLE_USER" of account "555" exists
        When I am authenticated in API as "user@greenhost.test"
        And I send a GET request to "/api/es/distributors/555/properties"
        Then the status code should be 403

    Scenario: Not authenticated user cannot list the properties of a distributor via API
        When I send a GET request to "/api/es/distributors/555/properties"
        Then the status code should be 403

    Scenario: As admin I get a not found when the account is not a distributor
        When I am authenticated in API as "admin@dediez.software"
        And I send a GET request to "/api/es/distributors/1111/properties"
        Then the status code should be 404

    Scenario: As admin I get a not found when the account does not exist
        When I am authenticated in API as "admin@dediez.software"
        And I send a GET request to "/api/es/distributors/9999/properties"
        Then the status code should be 404

    Scenario: As admin I get an empty list for a distributor without properties
        Given account "Agencia" with id "777" exists
        Given account "777" is a distributor
        When I am authenticated in API as "admin@dediez.software"
        And I send a GET request to "/api/es/distributors/777/properties"
        Then the status code should be 200
        And the response JSON should be equal to:
        """
        []
        """
