Feature:
    Ability for owners to see the CO2 credit balance of their account

    Scenario: As an owner I can see the available tonnes for a validated purchase
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes for "150" euros and reference "REF-1"
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits"
        Then the response status should be 200
        Then I see a text "Item 1"

    Scenario: As an owner I can see the transaction history filtered by project via link
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given project "Item 2" with id "3333" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "4444" for account "666" and project "3333" requesting "5" tonnes exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/purchases?projectId=1111"
        Then the response status should be 200
        Then I see a text "Item 1"
