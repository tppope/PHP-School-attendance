$(window).on("load", function() {

    loadData();
    setLottiePlayer();
    const getCellValueMinutes = (tr, idx) => parseFloat(tr.children[idx].innerText) || parseFloat(tr.children[idx].textContent);
    const getCellValueSurname = (tr, idx) => getSurname(tr.children[idx].innerText) || getSurname(tr.children[idx].textContent);
    const getCellValueAttendanceCount = (tr, idx) => parseInt(tr.children[idx].innerText) || parseInt(tr.children[idx].textContent);
    sortRowsBy(getCellValueAttendanceCount,'#put-lectures-before-me',getCellValueSurname);
    sortRowsBy(getCellValueSurname,'#sort-surname',null);
    sortRowsBy(getCellValueMinutes,'#sort-minutes',getCellValueSurname);
});
let myChart =null;
function loadData(){
    showLoadingScreen();
    eraseOldData();
    checkAttendance().then(r =>
        getLectures().then(r =>
            getAttendanceData().then(r =>
                getUserCount().then(r=>
                    hideLoadingScreen()
                )
            )
        )
    );

}

function showGraph(){
    showModal($("#attendance-graph"));
}
function eraseOldData(){
    $("#table-body").empty();
    $(".lecture-class").remove();
}
function setLottiePlayer(){
    const player = $("#refresh-lottie").get(0);
    $("#lottie-hover").on({
        mouseenter: function (){
            player.play();
        },
        mouseleave:function (){
            player.pause();
        }
    })
}
function hideLoadingScreen(){
    let loadingScreen = $("#loading-screen");
    const player = $("#loading-animation").get(0);
    player.pause();
    loadingScreen.css("opacity","0");
    setTimeout(performHide,700);
}
function performHide(){
    $("#loading-screen").hide();
}
function showLoadingScreen(){
    let loadingScreen = $("#loading-screen");
    const player = $("#loading-animation").get(0);
    player.play();
    loadingScreen.show();
    loadingScreen.css("opacity","1");

}

async function checkAttendance() {
    await fetch('api/loadAttendance.php')
        .then(response => response.json())
        .then(data => {
            console.log(data.status);
        });
}

async function getLectures() {
    await fetch('api/readAttendance.php?actionToDo=getLectures')
        .then(response => response.json())
        .then(data => {
            createTableHeader(data.lectures);
        });
}

async function getAttendanceData() {
    await fetch('api/readAttendance.php?actionToDo=getAttendanceData')
        .then(response => response.json())
        .then(data => {
            printUserAttendance(data.userAttendance);
        });
}
function sortRowsBy(getCellValue,id,getCellValueSurname){
    $(id).on("click",function () {
        if (getCellValueSurname){
            let surnameTh = $('#sort-surname').get(0);
            doSort(getCellValueSurname, surnameTh,true)
        }
        doSort(getCellValue,this,this.asc = !this.asc);
        arrowSwitch(this.asc,$(this).find("img"));
    });
}
function doSort(getCellValue, th,asc){
    const comparer = (idx, asc) => (a, b) => ((v1, v2) =>
            v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2,'sk')
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

    const table = th.closest('table');
    const tbody = table.querySelector('tbody');
    Array.from(tbody.querySelectorAll('tr'))
        .sort(comparer(Array.from(th.parentNode.children).indexOf(th), asc))
        .forEach(tr => tbody.appendChild(tr) );
}
function arrowSwitch(asc,arrows){
    $(".sort-arrows").find("img").css("visibility","visible");
    if (asc)
        $(arrows.get(1)).css("visibility","hidden");
    else
        $(arrows.get(0)).css("visibility","hidden");
}
function getSurname(fullName){
    return fullName.slice(fullName.indexOf(" ")+1);
}
function printUserAttendance(userAttendance){
    $.each(userAttendance,function (){
        let tableRow = createTr($("#table-body"));
        let user = this;
        tableRow.append(createTh(user.name + " "+user.surname));
        $.each(user.lectures, function (index,lecture){
            let td = createMinuteTd(lecture.minutes);
            $(td).on("click",()=>showInfoInModal(user,lecture,index+1));
            if (!lecture.isLeft)
                $(td).css("color","#B00D23");
            $(td).addClass("clickable-td");
            tableRow.append(td);
        })
        tableRow.append(createTd(user.attendanceCount),createMinuteTd(user.minutes))
    })
}

function showInfoInModal(user, lecture, lectureIndex){
    showUserInfoInModal(user);
    showLectureInfoInModal(lecture, lectureIndex)
    showModal($('#attendance-detail'));
}
function showUserInfoInModal(user){
    $("#attendance-detail-title").text(user.name +" " + user.surname);
    let attendanceCount = $("<li>Celkový počet účastí na prednáškach: "+user.attendanceCount+"</li>");
    let lecturesMinutes = $("<li>Celkový počet minút strávených na prednáškach: "+user.minutes+"</li>");
    let userInfoUl=$("#user-info");
    userInfoUl.empty();
    userInfoUl.append(attendanceCount,lecturesMinutes);

}

function showLectureInfoInModal(lecture, lectureIndex){
    $("#lecture-detail-title").text(lectureIndex +". prednáška - " + changeDateFormat(lecture.date));
    showAttendanceOfLectureInUserInfoTable(lecture.attendance);

}
function showAttendanceOfLectureInUserInfoTable(attendance){
    let tbodyUserInfo = $("#user-info-tbody");
    tbodyUserInfo.empty();
    if (attendance.length ===0)
        tbodyUserInfo.append($("<tr><td id='notAttend' colspan='3'>Študent za nezúčastnil tejto prednášky</td></tr>").get(0))
    let i = 1;
    let order = 1;
    for (;i<attendance.length;i = i +2) {
        createTr(tbodyUserInfo).append(createTh(order+"."),createTd(changeTimestampFormat(attendance[i-1].timestamp)),createTd(changeTimestampFormat(attendance[i].timestamp)));
        order++;
    }
    if (attendance.length % 2 !==0)
        createTr(tbodyUserInfo).append(createTh(order+"."),createTd(changeTimestampFormat(attendance[i-1].timestamp)),createTd("-"));

}
function changeTimestampFormat(timestamp){
    let timestampArray = timestamp.split(" ");
    return timestampArray[1];
}
function showModal(modalToShow){
    modalToShow.modal({
        keyboard: false
    });
}
function createMinuteTd(text){
    let td = document.createElement("td");
    if (!text){
        $(td).html("<strong>&#8212;</strong>");
        $(td).css("text-align","center");
    }else
        $(td).html(text+" min");
    return td;
}
function createTh(text){
    let th = document.createElement("th");
    $(th).text(text);
    return th;
}
function createTd(text){
    let td = document.createElement("td");
    $(td).text(text);
    return td;
}
function createTr(tbody){
    let tr = document.createElement("tr");
    tbody.append(tr);
    return tr;
}

function createTableHeader(lectures){
    let putBeforeMe = $("#put-lectures-before-me");
    $.each(lectures, function (index,element){
        putBeforeMe.before(createLectureTh(element.date,index+1));
    })
}

function createLectureTh(text, order){
    let th = document.createElement("th");
    $(th).addClass("lecture-class");
    $(th).html(order+". prednáška<br>"+changeDateFormat(text));
    return th;
}
function changeDateFormat(day){
    let dayArray = day.split("-");
    return parseInt(dayArray[2]) +"."+parseInt(dayArray[1])+". "+parseInt(dayArray[0]);
}
async function getUserCount() {
    await fetch('api/readAttendance.php?actionToDo=getUserCount')
        .then(response => response.json())
        .then(data => {
            createGraph(getGraphData(data.userCount));
        });
}
function getGraphData(userCountArray){
    let graphData = {
        labels:[],
        userCount:[],
    };
    $.each(userCountArray,function (index){
        let label = [];
        label.push(index+1+". prednáška");
        label.push(" "+this.date);
        graphData.labels.push(label);
        graphData.userCount.push(this.userCount);
    });
    return graphData;
}
function createGraph(graphData){
    let ctx = $("#myChart");
    if (myChart)
        myChart.destroy();
    myChart = new Chart(ctx,{
        type: 'bar',
        data: {
            labels: graphData.labels,
            datasets:[
                {
                    label:"Počet študentov",
                    data: graphData.userCount,
                    backgroundColor: 'rgba(0, 73, 83)',
                    borderWidth:1
                }
            ]
        },
        options: {
            scales: {
                y:{
                    beginAtZero: true
                }
            },
            plugins:{
                legend:{
                    display:false,
                }
            }

        }
    });
}

