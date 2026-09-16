Feature:
    Ability to calculate the emissions of a refrigerant gas leak for an event carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint for event "333" of account "666" exists
        Given refrigerant gas "R-410A" with id "RG1" and emission factor for year "2026" exists
        Given refrigerant gas "R-32" with id "RG2" exists

    Scenario: An owner calculates the emissions of a leak measured in kilograms
        When I am logged as "owner@dediez.software"
        When I calculate emissions for event "333" with type "refrigerant_gas", item "RG1", quantity "2" and unit "kg"
        Then the response status should be 200
        Then the calculated co2eq emissions should be "3"

    Scenario: A leak measured in tonnes is converted to kilograms
        When I am logged as "owner@dediez.software"
        When I calculate emissions for event "333" with type "refrigerant_gas", item "RG1", quantity "2" and unit "t"
        Then the response status should be 200
        Then the calculated co2eq emissions should be "3000"

    Scenario: A quantity in a unit that is not of mass is rejected
        When I am logged as "owner@dediez.software"
        When I calculate emissions for event "333" with type "refrigerant_gas", item "RG1", quantity "2" and unit "l"
        Then the response status should be 422

    Scenario: A refrigerant gas without emission factor is rejected
        When I am logged as "owner@dediez.software"
        When I calculate emissions for event "333" with type "refrigerant_gas", item "RG2", quantity "2" and unit "kg"
        Then the response status should be 422

    Scenario: A user without access cannot calculate emissions
        Given user "user@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user@dediez.software"
        When I calculate emissions for event "333" with type "refrigerant_gas", item "RG1", quantity "2" and unit "kg"
        Then the response status should be 403
