Feature:
    Ability to see how a purchased credit is split into compensations

    Background:
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given project "Reforestación" with id "1111" and price per tonne "20" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        Given contribution "C1" of account "666" for project "1111" with "2" tonnes is pending until "+12 hours"
        When the gateway notifies that the payment of contribution "C1" succeeded

    Scenario: As an owner I can see the compensations of a purchased credit
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/purchases/2222"
        Then the response status should be 200
        Then I see a text "Compensaciones"
        Then I see a text "REF-1"

    Scenario: As an owner I can see the split of each purchase in my credits page
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits"
        Then the response status should be 200
        Then I see a text "Reforestación"
        Then I see a text "REF-1"
        Then I see a text "compensadas"

    Scenario: As an admin I can see the compensations of a purchased credit
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222"
        Then the response status should be 200
        Then I see a text "Compensaciones"
        Then I see a text "REF-1"

    Scenario: As an owner I cannot see the compensations of a credit of another account
        Given account "OtherCompany" with id "999" exists
        Given user "other@dediez.software" with role "ROLE_OWNER" of account "999" exists
        When I am logged as "other@dediez.software"
        When I go to "/es/credits/purchases/2222"
        Then the response status should be 403
