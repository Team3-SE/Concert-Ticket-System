
*** Settings ***
Library    SeleniumLibrary
Library    Collections
Library    String

*** Variables ***
${URL}          https://www.saucedemo.com
${BROWSER}      chrome
${USERNAME}     standard_user
${PASSWORD}     secret_sauce


*** Test Cases ***
SauceDemo Dynamic End To End Test

    # =========================
    # Open Browser & Login
    # =========================
    Open Browser    ${URL}    ${BROWSER}
    Maximize Browser Window

    Input Text    id=user-name    ${USERNAME}
    Input Text    id=password     ${PASSWORD}
    Click Button    id=login-button

    Wait Until Page Contains Element    css=.inventory_list


    # =========================
    # Sorting: Price High To Low
    # =========================
    Select From List By Value
    ...    css=.product_sort_container
    ...    hilo

    # ดึงราคาสินค้าทั้งหมดหลัง Sort
    ${price_elements}=    Get WebElements    css=.inventory_item_price

    ${prices}=    Create List

    FOR    ${element}    IN    @{price_elements}

        ${price}=    Get Text    ${element}
        ${price}=    Remove String    ${price}    $
        ${price}=    Convert To Number    ${price}

        Append To List    ${prices}    ${price}

    END

    Log    All Prices: ${prices}


    # =========================
    # Dynamic Inspection
    # ตรวจสอบราคาชิ้นแรก >= ชิ้นถัดไป
    # =========================
    ${first_price}=     Get From List    ${prices}    0
    ${second_price}=    Get From List    ${prices}    1

    Should Be True    ${first_price} >= ${second_price}

    Log    First Price: ${first_price}
    Log    Second Price: ${second_price}


    # =========================
    # Find Most Expensive / Cheapest
    # =========================
    ${max_price}=    Evaluate    max(${prices})
    ${min_price}=    Evaluate    min(${prices})

    Log    Most Expensive: ${max_price}
    Log    Cheapest: ${min_price}


    # =========================
    # Find Index Of Most Expensive
    # =========================
    ${max_index}=    Get Index From List
    ...    ${prices}
    ...    ${max_price}

    Log    Most Expensive Index: ${max_index}


    # =========================
    # Add Most Expensive Product
    # =========================
    ${buttons}=    Get WebElements
    ...    css=.inventory_item button

    ${max_button}=    Get From List
    ...    ${buttons}
    ...    ${max_index}

    Click Element    ${max_button}

    Log    Added Most Expensive Product: ${max_price}


    # =========================
    # Find Index Of Cheapest
    # =========================
    ${min_index}=    Get Index From List
    ...    ${prices}
    ...    ${min_price}

    Log    Cheapest Index: ${min_index}


    # =========================
    # Add Cheapest Product
    # =========================
    # ดึงปุ่มใหม่อีกครั้งหลังจากกดสินค้าตัวแรก
    ${buttons}=    Get WebElements
    ...    css=.inventory_item button

    ${min_button}=    Get From List
    ...    ${buttons}
    ...    ${min_index}

    Click Element    ${min_button}

    Log    Added Cheapest Product: ${min_price}


    # =========================
    # Verify Cart Badge = 2
    # =========================
    Wait Until Element Is Visible
    ...    css=.shopping_cart_badge
    ...    5s

    Element Text Should Be
    ...    css=.shopping_cart_badge
    ...    2



    # =========================
    # Cart Navigation
    # =========================
    Click Link    class:shopping_cart_link

    Wait Until Page Contains Element    css=.cart_item

    ${cart_items}=    Get Element Count    css=.cart_item

    Should Be Equal As Integers
    ...    ${cart_items}
    ...    2


    # =========================
    # Checkout
    # =========================
    Click Button    id=checkout

    Wait Until Page Contains Element    id=first-name

    Input Text    id=first-name    Jirayus
    Input Text    id=last-name     Arbking
    Input Text    id=postal-code   10110

    Click Button    id=continue

    Wait Until Page Contains    Overview


    # =========================
    # Get Prices From Overview
    # =========================
    ${overview_prices}=    Get WebElements
    ...    css=.cart_item .inventory_item_price

    ${overview_total}=    Set Variable    0

    FOR    ${element}    IN    @{overview_prices}

        ${price}=    Get Text    ${element}
        ${price}=    Remove String    ${price}    $
        ${price}=    Convert To Number    ${price}

        ${overview_total}=    Evaluate
        ...    ${overview_total} + ${price}

    END

    Log    Calculated Item Total: ${overview_total}


    # =========================
    # Get Item Total From Website
    # =========================
    ${item_total_text}=    Get Text
    ...    css=.summary_subtotal_label

    ${item_total_text}=    Remove String
    ...    ${item_total_text}
    ...    Item total: $

    ${actual_item_total}=    Convert To Number
    ...    ${item_total_text}

    Log    Website Item Total: ${actual_item_total}


    # =========================
    # Verify Item Total
    # =========================
    Should Be Equal As Numbers
    ...    ${actual_item_total}
    ...    ${overview_total}
    ...    0.01


    # =========================
    # Calculate Tax 8%
    # =========================
    ${expected_tax}=    Evaluate
    ...    round(${actual_item_total} * 0.08, 2)

    ${tax_text}=    Get Text
    ...    css=.summary_tax_label

    ${tax_text}=    Remove String
    ...    ${tax_text}
    ...    Tax: $

    ${actual_tax}=    Convert To Number
    ...    ${tax_text}

    Log    Expected Tax: ${expected_tax}
    Log    Actual Tax: ${actual_tax}


    # =========================
    # Verify Tax
    # =========================
    Should Be Equal As Numbers
    ...    ${actual_tax}
    ...    ${expected_tax}
    ...    0.01


    # =========================
    # Calculate Total
    # Item Total + Tax = Total
    # =========================
    ${expected_total}=    Evaluate
    ...    round(${actual_item_total} + ${actual_tax}, 2)

    ${total_text}=    Get Text
    ...    css=.summary_total_label

    ${total_text}=    Remove String
    ...    ${total_text}
    ...    Total: $

    ${actual_total}=    Convert To Number
    ...    ${total_text}

    Log    Expected Total: ${expected_total}
    Log    Actual Total: ${actual_total}


    # =========================
    # Verify Total
    # =========================
    Should Be Equal As Numbers
    ...    ${actual_total}
    ...    ${expected_total}
    ...    0.01


    # =========================
    # Finish
    # =========================
    Click Button    id=finish

    Wait Until Location Is
    ...    ${URL}/checkout-complete.html

    Location Should Be
    ...    ${URL}/checkout-complete.html


    # =========================
    # Thank You Message
    # =========================
    Element Should Contain
    ...    css=.complete-header
    ...    Thank you for your order!


    [Teardown]    Close All Browsers
