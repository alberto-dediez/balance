Feature:
    I want to download the justified exclusions of the scope 3 of a completed organizational carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists

    Scenario: The owner downloads the justified exclusions document of a completed property footprint with materiality decisions
        Given standard materiality topic "10" with id "T10" exists
        Given materiality question "¿Qué magnitud tiene?" with id "Q10" for criterion "magnitude" and topic "T10" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given materiality assessment with id "A1" for "property" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" is completed
        Given materiality decisions for "property" carbon footprint "999":
            | topicId | topicNumber | score | threshold | forced | verdict   |
            | T10     | 10          | 0     | 50        | no     | omit      |
        Given property carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprints/property/999/justified-exclusions"
        Then the response status should be 200
        Then the response header "Content-Type" should be "application/pdf"

    Scenario: The owner downloads the justified exclusions document of a completed company footprint with materiality decisions
        Given company carbon footprint with id "CCF1" for company "C1" of account "666" and year "2025" with employees answered exists
        Given materiality decisions for "company" carbon footprint "CCF1":
            | topicNumber | score | threshold | forced | verdict   |
            | 10          | 0     | 50        | no     | omit      |
            | 1           | 62.5  | 50        | no     | calculate |
        Given company carbon footprint "CCF1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprints/company/CCF1/justified-exclusions"
        Then the response status should be 200
        Then the response header "Content-Type" should be "application/pdf"

    Scenario: The owner downloads the justified exclusions document of a footprint without materiality assessment
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprints/property/999/justified-exclusions"
        Then the response status should be 200
        Then the response header "Content-Type" should be "application/pdf"

    Scenario: A user of another account cannot download it
        Given account "OtherCompany" with id "777" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        When I am logged as "other@dediez.software"
        When I go to "/es/carbon-footprints/property/999/justified-exclusions"
        Then the response status should be 403

    Scenario: The document of an open footprint cannot be downloaded
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprints/property/999/justified-exclusions"
        Then the response status should be 409

    Scenario: The document of an open company footprint cannot be downloaded
        Given company carbon footprint with id "CCF1" for company "C1" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprints/company/CCF1/justified-exclusions"
        Then the response status should be 409

    Scenario: An unknown footprint is not found
        When I am logged as "owner@dediez.software"
        When I go to "/es/carbon-footprints/property/unknown/justified-exclusions"
        Then the response status should be 404

    Scenario: The action is listed in the wizard of a completed footprint
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        Given property carbon footprint "999" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/results"
        Then the response status should be 200
        Then I see a link to "/es/carbon-footprints/property/999/justified-exclusions"

    Scenario: The action is not listed while the footprint is open
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/property-carbon-footprints/999/step/scope-1"
        Then the response status should be 200
        Then I do not see a link with name "Exclusiones justificadas"

    Scenario: The action is listed in the wizard of a completed company footprint
        Given company carbon footprint with id "CCF1" for company "C1" of account "666" and year "2025" with employees answered exists
        Given company carbon footprint "CCF1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/CCF1/step/results"
        Then the response status should be 200
        Then I see a link to "/es/carbon-footprints/company/CCF1/justified-exclusions"

    Scenario: The action is not listed while the company footprint is open
        Given company carbon footprint with id "CCF1" for company "C1" of account "666" and year "2025" with employees answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/CCF1/step/scope-1"
        Then the response status should be 200
        Then I do not see a link with name "Exclusiones justificadas"
